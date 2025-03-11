<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('respond')
                ->label(__('Répondre'))
                ->icon('heroicon-o-chat-bubble-left')
                ->form([
                    RichEditor::make('content')
                        ->label(__('Réponse'))
                        ->required()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('tickets/responses'),
                    Toggle::make('is_internal')
                        ->label(__('Commentaire interne'))
                        ->helperText(__('Les commentaires internes ne sont pas visibles par le client'))
                        ->default(false),
                ])
                ->action(function (array $data, Ticket $record): void {
                    $response = $record->responses()->create([
                        'content' => $data['content'],
                        'user_id' => auth()->id(),
                        'is_internal' => $data['is_internal'],
                    ]);

                    // Mise à jour du statut du ticket si ce n'est pas un commentaire interne
                    if (!$data['is_internal']) {
                        $record->update(['status' => 'en_attente']);
                    }

                    Notification::make()
                        ->title(__('Réponse ajoutée avec succès'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('change_status')
                ->label(__('Changer le statut'))
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Select::make('status')
                        ->label(__('Nouveau statut'))
                        ->options([
                            'ouvert' => __('Ouvert'),
                            'en_attente' => __('En attente'),
                            'résolu' => __('Résolu'),
                            'fermé' => __('Fermé'),
                        ])
                        ->required(),
                ])
                ->action(function (array $data, Ticket $record): void {
                    $oldStatus = $record->status;
                    $record->update(['status' => $data['status']]);
                    
                    // Si le ticket est marqué comme résolu, enregistrer la date
                    if ($data['status'] === 'résolu' && $oldStatus !== 'résolu') {
                        $record->update(['resolved_at' => now()]);
                    }
                    
                    Notification::make()
                        ->title(__('Statut mis à jour avec succès'))
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
