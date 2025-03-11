<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    public static function getTitle(): string
    {
        return __('Réponses');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('content')
                    ->label(__('Contenu'))
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('tickets/responses'),
                Forms\Components\Toggle::make('is_internal')
                    ->label(__('Commentaire interne'))
                    ->helperText(__('Les commentaires internes ne sont pas visibles par le client'))
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Utilisateur')),
                Tables\Columns\TextColumn::make('content')
                    ->label(__('Contenu'))
                    ->html()
                    ->limit(200),
                Tables\Columns\IconColumn::make('is_internal')
                    ->label(__('Interne'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Créé le'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('internal')
                    ->label(__('Commentaires internes'))
                    ->query(fn (Builder $query): Builder => $query->where('is_internal', true)),
                Tables\Filters\Filter::make('external')
                    ->label(__('Réponses externes'))
                    ->query(fn (Builder $query): Builder => $query->where('is_internal', false)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
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
}
