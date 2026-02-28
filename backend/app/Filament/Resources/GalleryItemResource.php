<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryItemResource\Pages;
use App\Models\GalleryItem;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GalleryItemResource extends Resource
{
    protected static ?string $model = GalleryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Сайт';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Галерея';

    protected static ?string $modelLabel = 'Элемент галереи';

    protected static ?string $pluralModelLabel = 'Галерея';

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
                FileUpload::make('image')
                    ->label('Изображение')
                    ->disk('public')
                    ->directory('gallery')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(10240)
                    ->required(static fn (string $operation): bool => $operation === 'create'),

                TextInput::make('caption')
                    ->label('Подпись')
                    ->maxLength(255),

                TextInput::make('alt')
                    ->label('ALT')
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->required()
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Фото'),
                TextColumn::make('caption')->label('Подпись')->searchable()->wrap(),
                TextColumn::make('alt')->label('ALT')->toggleable(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Статус'),
            ])
            ->actions([
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
            'index' => Pages\ManageGalleryItems::route('/'),
        ];
    }
}
