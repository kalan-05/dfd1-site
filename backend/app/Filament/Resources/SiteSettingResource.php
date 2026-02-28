<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Сайт';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $modelLabel = 'Настройки сайта';

    protected static ?string $pluralModelLabel = 'Настройки сайта';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return (auth()->user()?->isAdmin() ?? false) && ! SiteSetting::query()->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('site_name')
                    ->label('Название сайта')
                    ->required()
                    ->maxLength(255),

                Fieldset::make('Контакты')
                    ->schema([
                        TextInput::make('phone_1')->label('Телефон 1')->maxLength(50),
                        TextInput::make('phone_2')->label('Телефон 2')->maxLength(50),
                        TextInput::make('email')->label('Email')->email()->maxLength(255),
                        Textarea::make('address_main')->label('Адрес')->rows(2),
                        TextInput::make('worktime_main')->label('График работы')->maxLength(255),
                    ])
                    ->columns(2),

                Fieldset::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                        Textarea::make('seo_description')->label('SEO description')->rows(3),
                        Textarea::make('seo_keywords')->label('SEO keywords')->rows(2),
                        FileUpload::make('og_image')
                            ->label('OG изображение')
                            ->disk('public')
                            ->directory('settings')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240),
                    ])
                    ->columns(2),

                Fieldset::make('Карта')
                    ->schema([
                        TextInput::make('map_lat')->label('Широта')->numeric()->step(0.000001),
                        TextInput::make('map_lng')->label('Долгота')->numeric()->step(0.000001),
                        TextInput::make('map_zoom')->label('Масштаб')->numeric()->minValue(1)->maxValue(20),
                    ])
                    ->columns(3),

                KeyValue::make('social')
                    ->label('Социальные ссылки')
                    ->keyLabel('Канал')
                    ->valueLabel('URL')
                    ->addActionLabel('Добавить ссылку')
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site_name')->label('Название')->searchable(),
                TextColumn::make('phone_1')->label('Телефон'),
                TextColumn::make('email')->label('Email'),
                TextColumn::make('updated_at')->label('Обновлено')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSiteSettings::route('/'),
        ];
    }
}
