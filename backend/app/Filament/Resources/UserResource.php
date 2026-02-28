<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Администрирование';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    protected static function canManage(): bool
    {
        return auth()->user()?->canManageUsers() ?? false;
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
        if (! static::canManage()) {
            return false;
        }

        $currentUserId = auth()->id();
        if ($currentUserId !== null && (int) $record->getKey() === (int) $currentUserId) {
            return false;
        }

        if ($record instanceof User && $record->isSuperAdmin()) {
            return User::query()->where('role', 'super_admin')->count() > 1;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->minLength(8)
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Оставьте пустым, чтобы не менять пароль.'),

                Select::make('role')
                    ->label('Роль')
                    ->required()
                    ->default('editor')
                    ->native(false)
                    ->options([
                        'super_admin' => 'Главный администратор',
                        'admin' => 'Администратор',
                        'editor' => 'Редактор',
                    ]),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => match ($state) {
                        'super_admin' => 'Главный администратор',
                        'admin' => 'Администратор',
                        default => 'Редактор',
                    })
                    ->color(static fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'super_admin' => 'Главный администратор',
                        'admin' => 'Администратор',
                        'editor' => 'Редактор',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(static fn (User $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}

