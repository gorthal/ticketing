<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Utilisateurs');
    }

    public static function getModelLabel(): string
    {
        return __('Utilisateur');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Utilisateurs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informations'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nom'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label(__('Mot de passe'))
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\Select::make('role_id')
                            ->label(__('Rôle'))
                            ->relationship('role', 'name')
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('company_id')
                            ->label(__('Société'))
                            ->relationship('company', 'name')
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Actif'))
                            ->default(true)
                            ->required(),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label(__('Email vérifié à')),
                    ])->columns(2),
                Forms\Components\Section::make(__('Labels assignés'))
                    ->schema([
                        CheckboxList::make('labels')
                            ->label(__('Labels'))
                            ->relationship('labels', 'name')
                            ->descriptions(fn ($value, $record) => $record?->color)
                            ->bulkToggleable()
                            ->columns(3)
                            ->visible(function ($record, $get) {
                                $roleId = $get('role_id');
                                return $roleId && \App\Models\Role::find($roleId)?->name === 'agent';
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nom'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label(__('Rôle'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => __('Administrateur'),
                        'agent' => __('Agent'),
                        'client' => __('Client'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'agent' => 'warning',
                        'client' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('Société'))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Actif'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label(__('Email vérifié'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Créé le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Mis à jour le'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('Rôle'))
                    ->relationship('role', 'name')
                    ->options([
                        'admin' => __('Administrateur'),
                        'agent' => __('Agent'),
                        'client' => __('Client'),
                    ]),
                Tables\Filters\SelectFilter::make('company')
                    ->label(__('Société'))
                    ->relationship('company', 'name'),
                Tables\Filters\Filter::make('verified')
                    ->label(__('Email vérifié'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Tables\Filters\Filter::make('unverified')
                    ->label(__('Email non vérifié'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
