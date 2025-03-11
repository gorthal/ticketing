<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public static function getTitle(): string
    {
        return __('Pièces jointes');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label(__('Fichier'))
                    ->required()
                    ->disk('public')
                    ->directory('tickets/attachments')
                    ->visibility('public')
                    ->acceptedFileTypes([
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/zip', 'application/x-zip-compressed'
                    ])
                    ->maxSize(10240) // 10 Mo
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) return;
                        
                        $fileName = $state[0] ?? $state;
                        $originalName = $fileName;
                        $fileSize = Storage::disk('public')->size($fileName);
                        $fileType = Storage::disk('public')->mimeType($fileName);
                        $isImage = str_starts_with($fileType, 'image/');
                        
                        $set('file_name', $fileName);
                        $set('file_type', $fileType);
                        $set('file_size', round($fileSize / 1024)); // Ko
                        $set('is_image', $isImage);
                        $set('original_name', $originalName);
                    }),
                Forms\Components\Hidden::make('file_name'),
                Forms\Components\Hidden::make('file_type'),
                Forms\Components\Hidden::make('file_size'),
                Forms\Components\Hidden::make('is_image'),
                Forms\Components\Hidden::make('original_name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label(__('Nom du fichier')),
                Tables\Columns\TextColumn::make('file_type')
                    ->label(__('Type')),
                Tables\Columns\TextColumn::make('file_size')
                    ->label(__('Taille'))
                    ->formatStateUsing(fn (int $state): string => "{$state} Ko"),
                Tables\Columns\IconColumn::make('is_image')
                    ->label(__('Image'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Ajouté le'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('images')
                    ->label(__('Images uniquement'))
                    ->query(fn (Builder $query): Builder => $query->where('is_image', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label(__('Télécharger'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
