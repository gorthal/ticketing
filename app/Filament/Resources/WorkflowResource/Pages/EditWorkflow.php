<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('toggle_active')
                ->label(fn ($record) => $record->is_active ? __('Désactiver') : __('Activer'))
                ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                ->action(function ($record) {
                    $record->update(['is_active' => !$record->is_active]);
                    
                    $this->notify(
                        'success',
                        $record->is_active ? __('Workflow activé') : __('Workflow désactivé')
                    );
                }),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
