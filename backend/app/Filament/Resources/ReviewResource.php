<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Сайт';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Отзывы';

    protected static ?string $modelLabel = 'Отзыв';

    protected static ?string $pluralModelLabel = 'Отзывы';

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
        return static::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('author_name')
                    ->label('Автор')
                    ->required()
                    ->maxLength(120),

                Select::make('doctor_id')
                    ->label('Врач')
                    ->relationship('doctor', 'full_name')
                    ->searchable()
                    ->preload(),

                Select::make('rating')
                    ->label('Оценка')
                    ->required()
                    ->options([
                        1 => '1',
                        2 => '2',
                        3 => '3',
                        4 => '4',
                        5 => '5',
                    ])
                    ->native(false),

                Select::make('source')
                    ->label('Источник')
                    ->required()
                    ->default('manual')
                    ->options([
                        'form' => 'Форма',
                        'manual' => 'Вручную',
                    ]),

                Select::make('status')
                    ->label('Статус')
                    ->required()
                    ->default('draft')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликован',
                        'rejected' => 'Отклонён',
                    ]),

                DateTimePicker::make('published_at')
                    ->label('Дата публикации')
                    ->seconds(false),

                TextInput::make('author_contacts')
                    ->label('Контакты автора')
                    ->maxLength(255),

                Textarea::make('text')
                    ->label('Текст')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('author_name')->label('Автор')->searchable()->sortable(),
                TextColumn::make('doctor.full_name')->label('Врач')->placeholder('—')->searchable(),
                TextColumn::make('rating')->label('Оценка')->sortable(),
                TextColumn::make('source')
                    ->label('Источник')
                    ->badge()
                    ->color(static fn (string $state): string => $state === 'form' ? 'info' : 'gray'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(static fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('published_at')->label('Опубликован')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликован',
                        'rejected' => 'Отклонён',
                    ]),
                SelectFilter::make('source')
                    ->label('Источник')
                    ->options([
                        'form' => 'Форма',
                        'manual' => 'Вручную',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->label('Опубликовать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(static fn (Review $record): bool => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(static function (Review $record): void {
                        $record->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(static fn (Review $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(static function (Review $record): void {
                        $record->update([
                            'status' => 'rejected',
                            'published_at' => null,
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReviews::route('/'),
        ];
    }
}
