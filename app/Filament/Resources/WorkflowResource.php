<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('Workflows');
    }

    public static function getModelLabel(): string
    {
        return __('Workflow');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Workflows');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informations du workflow'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nom'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('keyword')
                            ->label(__('Mot-clé'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('Le mot-clé à rechercher dans le sujet ou le contenu')),
                        Forms\Components\Select::make('label_id')
                            ->label(__('Label'))
                            ->relationship('label', 'name')
                            ->required(),
                        Forms\Components\Select::make('match_type')
                            ->label(__('Type de correspondance'))
                            ->options([
                                'subject' => __('Sujet uniquement'),
                                'body' => __('Corps uniquement'),
                                'both' => __('Sujet et corps'),
                            ])
                            ->default('both')
                            ->required(),
                        Forms\Components\Toggle::make('is_case_sensitive')
                            ->label(__('Sensible à la casse'))
                            ->default(false)
                            ->helperText(__('La recherche sera sensible aux majuscules/minuscules')),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Actif'))
                            ->default(true),
                    ])->columns(2),
                Forms\Components\Section::make(__('Notifications'))
                    ->schema([
                        Forms\Components\Textarea::make('notification_emails')
                            ->label(__('Emails de notification'))
                            ->helperText(__('Liste d\'emails séparés par des virgules'))
                            ->placeholder('email1@example.com, email2@example.com')
                            ->nullable(),
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
                Tables\Columns\TextColumn::make('keyword')
                    ->label(__('Mot-clé'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('label.name')
                    ->label(__('Label'))
                    ->badge()
                    ->color(fn ($record) => $record->label ? $record->label->color : '#000000'),
                Tables\Columns\TextColumn::make('match_type')
                    ->label(__('Type de correspondance'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'subject' => __('Sujet uniquement'),
                        'body' => __('Corps uniquement'),
                        'both' => __('Sujet et corps'),
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_case_sensitive')
                    ->label(__('Sensible à la casse'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Actif'))
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('label')
                    ->label(__('Label'))
                    ->relationship('label', 'name'),
                Tables\Filters\Filter::make('is_active')
                    ->label(__('Actif'))
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? __('Désactiver') : __('Activer'))
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (Workflow $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('Activer'))
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('Désactiver'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
