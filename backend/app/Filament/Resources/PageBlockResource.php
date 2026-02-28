<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageBlockResource\Pages;
use App\Models\PageBlock;
use App\Models\Service;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class PageBlockResource extends Resource
{
    protected static ?string $model = PageBlock::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Сайт';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Блоки страницы';

    protected static ?string $modelLabel = 'Блок страницы';

    protected static ?string $pluralModelLabel = 'Блоки страницы';

    protected static function canManage(): bool
    {
        return auth()->user()?->isEditor() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canManage();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function currentKey(?Model $record, Get $get): string
    {
        $value = $record?->getAttribute('key');

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $value = $get('key');

        return is_string($value) ? trim($value) : '';
    }

    protected static function isBlock(string $expected, ?Model $record, Get $get): bool
    {
        return static::currentKey($record, $get) === $expected;
    }

    protected static function contentLabel(?Model $record, Get $get): string
    {
        return match (static::currentKey($record, $get)) {
            'hero' => 'Основной заголовок первого экрана',
            'about' => 'Вводный текст секции',
            'services' => 'Описание секции',
            'doctors' => 'Описание секции',
            'footer' => 'Текст о центре',
            default => 'Основной текст',
        };
    }

    protected static function contentHelper(?Model $record, Get $get): string|HtmlString|null
    {
        return match (static::currentKey($record, $get)) {
            'hero' => 'Этот текст показывается крупным заголовком на первом экране.',
            'about' => 'Этот текст показывается сразу под заголовком секции «О нас».',
            'services' => 'Этот текст показывается над списком направлений диагностики.',
            'doctors' => 'Этот текст показывается в блоке с командой врачей.',
            'footer' => 'Краткое описание центра в левой колонке footer.',
            default => null,
        };
    }

    protected static function servicesState(): array
    {
        return Service::query()
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (Service $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'group' => $service->group,
                'sort_order' => $service->sort_order,
                'is_active' => $service->is_active,
            ])
            ->all();
    }

    protected static function syncServices(array $items): void
    {
        $existing = Service::query()->get()->keyBy('id');
        $keptIds = [];

        foreach ($items as $index => $item) {
            $title = trim((string) ($item['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $payload = [
                'title' => $title,
                'group' => trim((string) ($item['group'] ?? '')),
                'sort_order' => (int) ($item['sort_order'] ?? (($index + 1) * 10)),
                'is_active' => (bool) ($item['is_active'] ?? true),
            ];

            $id = isset($item['id']) && $item['id'] !== null && $item['id'] !== '' ? (int) $item['id'] : null;

            if ($id !== null && $existing->has($id)) {
                /** @var Service $service */
                $service = $existing->get($id);
                $service->update($payload);
                $keptIds[] = $service->id;
                continue;
            }

            $service = Service::query()->create($payload);
            $keptIds[] = $service->id;
        }

        $idsToDelete = $existing->keys()->reject(static fn ($id): bool => in_array((int) $id, $keptIds, true))->all();

        if ($idsToDelete !== []) {
            Service::query()->whereIn('id', $idsToDelete)->delete();
        }
    }

    protected static function plainTextEditor(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->label(fn (?Model $record, Get $get): string => static::contentLabel($record, $get))
            ->helperText(fn (?Model $record, Get $get): string|HtmlString|null => static::contentHelper($record, $get))
            ->toolbarButtons([
                'h2',
                'h3',
                'bold',
                'italic',
                'underline',
                'strike',
                'blockquote',
                'bulletList',
                'orderedList',
                'link',
                'undo',
                'redo',
            ])
            ->dehydrateStateUsing(static function (?string $state): string {
                if (! $state) {
                    return '';
                }

                $plain = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '<p>'], ["\n", "\n", "\n", "\n", ''], $state);

                return trim(strip_tags($plain));
            })
            ->columnSpanFull();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основные настройки')
                    ->schema([
                        TextInput::make('key')
                            ->label('Системный ключ')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('title')
                            ->label('Заголовок секции')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label('Порядок вывода')
                            ->required()
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_enabled')
                            ->label('Показывать на сайте')
                            ->default(true),
                    ])
                    ->columns(2),

                static::plainTextEditor('content')
                    ->visible(fn (?Model $record, Get $get): bool => in_array(static::currentKey($record, $get), ['hero', 'about', 'services', 'doctors', 'footer'], true)),

                Select::make('meta.content_alignment')
                    ->label('Выравнивание текста')
                    ->options([
                        'left' => 'По левому краю',
                        'center' => 'По центру',
                        'right' => 'По правому краю',
                        'justify' => 'По ширине',
                    ])
                    ->default(fn (?Model $record, Get $get): string => static::currentKey($record, $get) === 'doctors' ? 'center' : 'left')
                    ->visible(fn (?Model $record, Get $get): bool => in_array(static::currentKey($record, $get), ['about', 'services', 'doctors', 'footer'], true)),

                Section::make('Шапка сайта')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('header', $record, $get))
                    ->schema([
                        Textarea::make('meta.logo_lines')
                            ->label('Текст рядом с логотипом')
                            ->rows(4)
                            ->helperText('Каждая новая строка будет показана с новой строки.')
                            ->columnSpanFull(),

                        TextInput::make('meta.logo_title')
                            ->label('Подсказка для логотипа')
                            ->maxLength(255),

                        TextInput::make('meta.logo_alt')
                            ->label('Alt-текст логотипа')
                            ->maxLength(255),

                        TextInput::make('meta.booking_label')
                            ->label('Текст кнопки записи')
                            ->maxLength(255),

                        TextInput::make('meta.booking_url')
                            ->label('Ссылка кнопки записи')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('meta.department_url')
                            ->label('Ссылка на страницу отделения')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Секция «О нас»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('about', $record, $get))
                    ->schema([
                        TextInput::make('meta.block1_title')
                            ->label('Заголовок первого списка')
                            ->maxLength(255),

                        TextInput::make('meta.block1_history_title')
                            ->label('Заголовок исторического блока')
                            ->maxLength(255),

                        Textarea::make('meta.block1_items')
                            ->label('Пункты первого списка')
                            ->rows(5)
                            ->helperText('Каждая новая строка будет отдельным пунктом.')
                            ->columnSpanFull(),

                        Textarea::make('meta.block1_history_text')
                            ->label('Текст исторического блока')
                            ->rows(4)
                            ->columnSpanFull(),

                        Textarea::make('meta.history_text')
                            ->label('Исторические абзацы')
                            ->rows(6)
                            ->helperText('Каждая новая строка будет отдельным абзацем.')
                            ->columnSpanFull(),

                        TextInput::make('meta.block2_title')
                            ->label('Заголовок второго блока')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('meta.block2_group1_title')
                            ->label('Заголовок группы 1')
                            ->maxLength(255),

                        TextInput::make('meta.block2_group2_title')
                            ->label('Заголовок группы 2')
                            ->maxLength(255),

                        Textarea::make('meta.block2_group1_items')
                            ->label('Пункты группы 1')
                            ->rows(5)
                            ->helperText('Каждая новая строка будет отдельным пунктом.'),

                        Textarea::make('meta.block2_group2_items')
                            ->label('Пункты группы 2')
                            ->rows(5)
                            ->helperText('Каждая новая строка будет отдельным пунктом.'),

                        TextInput::make('meta.block3_lead')
                            ->label('Лид третьего блока')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('meta.block3_diagnosis')
                            ->label('Список диагнозов')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('meta.block3_text')
                            ->label('Основной текст третьего блока')
                            ->rows(5)
                            ->columnSpanFull(),

                        TextInput::make('meta.block4_title')
                            ->label('Заголовок четвертого блока')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('meta.block4_items')
                            ->label('Пункты четвертого блока')
                            ->rows(6)
                            ->helperText('Каждая новая строка будет отдельным пунктом.')
                            ->columnSpanFull(),

                        Textarea::make('meta.final_text')
                            ->label('Финальные абзацы секции')
                            ->rows(6)
                            ->helperText('Каждая новая строка будет отдельным абзацем.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Секция «Диагностика»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('services', $record, $get))
                    ->schema([
                        Placeholder::make('services_notice')
                            ->label('Что здесь редактируется')
                            ->content(new HtmlString('Описание секции редактируется в поле выше. Ниже редактируется полный список направлений диагностики, который показывается на сайте.')),

                        Repeater::make('service_items')
                            ->label('Пункты диагностики')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('title')
                                    ->label('Текст пункта')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('group')
                                    ->label('Группа / колонка')
                                    ->maxLength(255),
                                TextInput::make('sort_order')
                                    ->label('Порядок')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Показывать')
                                    ->default(true),
                            ])
                            ->columns(5)
                            ->addActionLabel('Добавить пункт')
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): string => trim((string) ($state['title'] ?? '')) ?: 'Новый пункт')
                            ->formatStateUsing(fn ($state, ?PageBlock $record): array => ($record?->key ?? '') === 'services' ? static::servicesState() : (is_array($state) ? $state : []))
                            ->columnSpanFull(),
                    ]),

                Section::make('Секция «Врачи»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('doctors', $record, $get))
                    ->schema([
                        Placeholder::make('doctors_notice')
                            ->label('Карточки врачей')
                            ->content(new HtmlString('Имена, должности, фотографии и описания врачей редактируются в меню <strong>Сайт -> Врачи</strong>.')),

                        TextInput::make('meta.subtitle')
                            ->label('Подзаголовок над фотографией команды')
                            ->maxLength(255),

                        Select::make('meta.subtitle_alignment')
                            ->label('Выравнивание подзаголовка')
                            ->options([
                                'left' => 'По левому краю',
                                'center' => 'По центру',
                                'right' => 'По правому краю',
                                'justify' => 'По ширине',
                            ])
                            ->default('center'),

                        TextInput::make('meta.team_count_label')
                            ->label('Подпись над описанием')
                            ->helperText('Показывается без числа.')
                            ->maxLength(255),

                        Select::make('meta.team_heading_alignment')
                            ->label('Выравнивание подписи')
                            ->options([
                                'left' => 'По левому краю',
                                'center' => 'По центру',
                                'right' => 'По правому краю',
                                'justify' => 'По ширине',
                            ])
                            ->default('center'),

                        TextInput::make('meta.team_image_alt')
                            ->label('Alt-текст общей фотографии')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Секция «Галерея»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('gallery', $record, $get))
                    ->schema([
                        Placeholder::make('gallery_notice')
                            ->label('Фотографии галереи')
                            ->content(new HtmlString('Фотографии и подписи галереи редактируются в меню <strong>Сайт -> Галерея</strong>.')),

                        TextInput::make('meta.prev_label')
                            ->label('Текст кнопки «Назад»')
                            ->maxLength(255),

                        TextInput::make('meta.next_label')
                            ->label('Текст кнопки «Вперед»')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Секция «Отзывы»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('reviews', $record, $get))
                    ->schema([
                        Placeholder::make('reviews_notice')
                            ->label('Отзывы посетителей')
                            ->content(new HtmlString('Опубликованные отзывы и модерация находятся в меню <strong>Сайт -> Отзывы</strong>. Здесь редактируются только заголовки, подписи и текст формы.')),

                        TextInput::make('meta.doctor_prefix')
                            ->label('Префикс перед именем врача')
                            ->maxLength(255),

                        TextInput::make('meta.prev_label')
                            ->label('Текст кнопки «Назад»')
                            ->maxLength(255),

                        TextInput::make('meta.next_label')
                            ->label('Текст кнопки «Вперед»')
                            ->maxLength(255),

                        TextInput::make('meta.prev_aria_label')
                            ->label('Aria-label кнопки «Назад»')
                            ->maxLength(255),

                        TextInput::make('meta.next_aria_label')
                            ->label('Aria-label кнопки «Вперед»')
                            ->maxLength(255),

                        TextInput::make('meta.form_title')
                            ->label('Заголовок формы')
                            ->maxLength(255),

                        TextInput::make('meta.name_label')
                            ->label('Подпись поля имени')
                            ->maxLength(255),

                        TextInput::make('meta.name_placeholder')
                            ->label('Подсказка поля имени')
                            ->maxLength(255),

                        TextInput::make('meta.doctor_label')
                            ->label('Подпись выбора врача')
                            ->maxLength(255),

                        TextInput::make('meta.doctor_placeholder')
                            ->label('Подсказка выбора врача')
                            ->maxLength(255),

                        TextInput::make('meta.rating_label')
                            ->label('Подпись поля оценки')
                            ->maxLength(255),

                        TextInput::make('meta.rating_placeholder')
                            ->label('Подсказка поля оценки')
                            ->maxLength(255),

                        TextInput::make('meta.review_text_label')
                            ->label('Подпись текста отзыва')
                            ->maxLength(255),

                        TextInput::make('meta.review_text_placeholder')
                            ->label('Подсказка текста отзыва')
                            ->maxLength(255),

                        TextInput::make('meta.submit_label')
                            ->label('Текст кнопки отправки')
                            ->maxLength(255),

                        TextInput::make('meta.submitting_label')
                            ->label('Текст кнопки во время отправки')
                            ->maxLength(255),

                        TextInput::make('meta.success_message')
                            ->label('Сообщение об успешной отправке')
                            ->maxLength(255),

                        TextInput::make('meta.error_message')
                            ->label('Сообщение при ошибке отправки')
                            ->maxLength(255),

                        TextInput::make('meta.network_error_message')
                            ->label('Сообщение при сетевой ошибке')
                            ->maxLength(255),

                        TextInput::make('meta.spam_message')
                            ->label('Сообщение при антиспаме')
                            ->maxLength(255),

                        TextInput::make('meta.captcha_missing_message')
                            ->label('Сообщение, если капча не настроена')
                            ->maxLength(255),

                        TextInput::make('meta.captcha_required_message')
                            ->label('Сообщение, если капча не подтверждена')
                            ->maxLength(255),

                        TextInput::make('meta.anonymous_label')
                            ->label('Подпись для анонимного автора')
                            ->maxLength(255),

                        Repeater::make('meta.initial_reviews')
                            ->label('Стартовые отзывы по умолчанию')
                            ->helperText('Показываются до загрузки опубликованных отзывов и используются как резервный контент.')
                            ->schema([
                                TextInput::make('author_name')
                                    ->label('Автор')
                                    ->required(),
                                TextInput::make('doctor_name')
                                    ->label('Врач'),
                                TextInput::make('rating')
                                    ->label('Оценка')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->default(5),
                                Textarea::make('text')
                                    ->label('Текст отзыва')
                                    ->rows(3)
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Добавить отзыв')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Секция «Контакты»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('contact', $record, $get))
                    ->schema([
                        Placeholder::make('contact_notice')
                            ->label('Контактные данные')
                            ->content(new HtmlString('Телефоны, email, адрес, график работы и координаты редактируются в меню <strong>Сайт -> Настройки сайта</strong>.')),
                    ]),

                Section::make('Секция «Карта»')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('map', $record, $get))
                    ->schema([
                        Placeholder::make('map_notice')
                            ->label('Координаты карты')
                            ->content(new HtmlString('Координаты, масштаб, адрес и телефон в балуне карты редактируются в меню <strong>Сайт -> Настройки сайта</strong>.')),

                        TextInput::make('meta.subtitle')
                            ->label('Подзаголовок под названием университета')
                            ->maxLength(255),

                        TextInput::make('meta.menu_label')
                            ->label('Подпись ссылки «Карта» в footer')
                            ->maxLength(255),

                        TextInput::make('meta.map_aria_label')
                            ->label('Aria-label карты')
                            ->maxLength(255),

                        Textarea::make('meta.fallback_text')
                            ->label('Текст при ошибке загрузки карты')
                            ->rows(2),

                        TextInput::make('meta.fallback_link_text')
                            ->label('Текст ссылки на Яндекс.Карты')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Подвал сайта')
                    ->visible(fn (?Model $record, Get $get): bool => static::isBlock('footer', $record, $get))
                    ->schema([
                        TextInput::make('meta.developer_label')
                            ->label('Подпись ссылки на разработчика')
                            ->maxLength(255),

                        TextInput::make('meta.developer_url')
                            ->label('Ссылка на разработчика')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('meta.developer_aria_label')
                            ->label('Aria-label ссылки на разработчика')
                            ->maxLength(255),

                        TextInput::make('meta.copyright')
                            ->label('Текст копирайта')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('key')->label('Ключ')->searchable()->sortable(),
                TextColumn::make('title')->label('Заголовок')->searchable()->wrap(),
                IconColumn::make('is_enabled')->label('Вкл.')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                TextColumn::make('updated_at')->label('Обновлено')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')->label('Статус'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->using(function (PageBlock $record, array $data): PageBlock {
                        $serviceItems = $data['service_items'] ?? null;
                        unset($data['service_items']);

                        $data['meta'] = array_replace($record->meta ?? [], $data['meta'] ?? []);

                        $record->update($data);

                        if ($record->key === 'services' && is_array($serviceItems)) {
                            static::syncServices($serviceItems);
                        }

                        return $record->refresh();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePageBlocks::route('/'),
        ];
    }
}
