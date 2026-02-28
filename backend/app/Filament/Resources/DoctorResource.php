<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Models\Doctor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Сайт';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Врачи';

    protected static ?string $modelLabel = 'Врач';

    protected static ?string $pluralModelLabel = 'Врачи';

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
                TextInput::make('full_name')
                    ->label('ФИО')
                    ->required()
                    ->maxLength(255),

                TextInput::make('position')
                    ->label('Должность')
                    ->required()
                    ->maxLength(255),

                TextInput::make('regalia')
                    ->label('Регалии')
                    ->maxLength(255),

                FileUpload::make('photo')
                    ->label('Фото')
                    ->disk('public')
                    ->directory('doctors')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(10240),

                Textarea::make('description')
                    ->label('Описание')
                    ->rows(5)
                    ->columnSpanFull(),

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
                ImageColumn::make('photo')->label('Фото')->circular(),
                TextColumn::make('full_name')->label('ФИО')->searchable()->sortable(),
                TextColumn::make('position')->label('Должность')->searchable(),
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
            'index' => Pages\ManageDoctors::route('/'),
        ];
    }
}
