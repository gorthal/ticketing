<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Label;
use App\Models\Ticket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Tickets';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Tickets');
    }

    public static function getModelLabel(): string
    {
        return __('Ticket');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tickets');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informations du ticket'))
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label(__('Sujet'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('content')
                            ->label(__('Contenu'))
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('tickets/attachments'),
                        Forms\Components\Select::make('status')
                            ->label(__('Statut'))
                            ->options([
                                'ouvert' => __('Ouvert'),
                                'en_attente' => __('En attente'),
                                'résolu' => __('Résolu'),
                                'fermé' => __('Fermé'),
                            ])
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->label(__('Client'))
                            ->options(fn () => User::whereHas('role', fn($q) => $q->where('name', 'client'))->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('assigned_agent_id')
                            ->label(__('Agent assigné'))
                            ->options(fn () => User::whereHas('role', fn($q) => $q->where('name', 'agent'))->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Toggle::make('is_archived')
                            ->label(__('Archivé'))
                            ->default(false),
                    ])->columns(2),
                Forms\Components\Section::make(__('Labels'))
                    ->schema([
                        CheckboxList::make('labels')
                            ->label(__('Labels assignés'))
                            ->relationship('labels', 'name')
                            ->options(fn () => Label::pluck('name', 'id'))
                            ->descriptions(fn ($value, $record) => Label::find($value)?->color ?? null)
                            ->bulkToggleable()
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('Sujet'))
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(__('Client'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Statut'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ouvert' => __('Ouvert'),
                        'en_attente' => __('En attente'),
                        'résolu' => __('Résolu'),
                        'fermé' => __('Fermé'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ouvert' => 'primary',
                        'en_attente' => 'warning',
                        'résolu' => 'success',
                        'fermé' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedAgent.name')
                    ->label(__('Agent assigné'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Créé le'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_archived')
                    ->label(__('Archivé'))
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TagsColumn::make('labels.name')
                    ->label(__('Labels'))
                    ->toggleable()
                    ->separator(', '),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Statut'))
                    ->options([
                        'ouvert' => __('Ouvert'),
                        'en_attente' => __('En attente'),
                        'résolu' => __('Résolu'),
                        'fermé' => __('Fermé'),
                    ]),
                Tables\Filters\SelectFilter::make('assigned_agent')
                    ->label(__('Agent assigné'))
                    ->relationship('assignedAgent', 'name'),
                Tables\Filters\Filter::make('unassigned')
                    ->label(__('Non assigné'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_agent_id')),
                Tables\Filters\Filter::make('archived')
                    ->label(__('Archivé'))
                    ->query(fn (Builder $query): Builder => $query->where('is_archived', true)),
                Tables\Filters\Filter::make('not_archived')
                    ->label(__('Non archivé'))
                    ->query(fn (Builder $query): Builder => $query->where('is_archived', false))
                    ->default(),
                Tables\Filters\SelectFilter::make('label')
                    ->label(__('Label'))
                    ->relationship('labels', 'name')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('respond')
                    ->label(__('Répondre'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (Ticket $record): string => route('filament.admin.resources.tickets.edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('Archiver'))
                        ->icon('heroicon-o-archive-box')
                        ->action(fn (Builder $query) => $query->update(['is_archived' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unarchive')
                        ->label(__('Désarchiver'))
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->action(fn (Builder $query) => $query->update(['is_archived' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ResponsesRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
