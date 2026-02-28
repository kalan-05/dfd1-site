<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Сайт';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Диагностика';

    protected static ?string $modelLabel = 'Пункт диагностики';

    protected static ?string $pluralModelLabel = 'Диагностика';

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
                TextInput::make('title')
                    ->label('Текст пункта диагностики')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('group')
                    ->label('Группа / колонка')
                    ->helperText('Например: Кардиодиагностика, УЗИ и функциональные тесты, левая колонка.')
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label('Порядок вывода')
                    ->required()
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Показывать на сайте')
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
                TextColumn::make('title')->label('Пункт диагностики')->searchable()->wrap(),
                TextColumn::make('group')->label('Группа')->sortable()->wrap(),
                IconColumn::make('is_active')->label('Показывать')->boolean(),
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
            'index' => Pages\ManageServices::route('/'),
        ];
    }
}