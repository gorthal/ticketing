<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Label;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidParameterException;

class EmailService
{
    /**
     * Récupère les emails de la boîte de réception et les convertit en tickets
     *
     * @return array Résumé de l'opération
     */
    public function fetchEmailsAndCreateTickets(): array
    {
        $summary = [
            'new_tickets' => 0,
            'updated_tickets' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];

        try {
            // Connexion au serveur IMAP
            $client = Client::account('default');
            $client->connect();

            // Récupération de la boîte de réception
            $folder = $client->getFolder('INBOX');
            
            // On récupère les emails non lus
            $messages = $folder->query()->unseen()->get();

            foreach ($messages as $message) {
                try {
                    $emailId = $message->getMessageId();
                    $subject = $message->getSubject();
                    $fromAddress = $message->getFrom()[0]->mail ?? null;
                    $fromName = $message->getFrom()[0]->personal ?? $fromAddress;
                    $body = $this->extractTextFromMessage($message);
                    $inReplyTo = $message->getInReplyTo();
                    
                    // Vérifier si l'email est une réponse à un ticket existant
                    if ($inReplyTo) {
                        $ticket = Ticket::where('email_id', $inReplyTo)->first();
                        
                        if ($ticket) {
                            // C'est une réponse à un ticket existant
                            $response = $ticket->responses()->create([
                                'content' => $body,
                                'user_id' => $ticket->client_id,
                                'is_internal' => false,
                                'email_id' => $emailId,
                            ]);
                            
                            // Mise à jour du statut du ticket
                            $ticket->update(['status' => 'ouvert']);
                            
                            // Traitement des pièces jointes
                            $this->processAttachments($message, null, $response->id);
                            
                            $summary['updated_tickets']++;
                            $summary['details'][] = "Ticket #{$ticket->id} mis à jour via email: {$subject}";
                            
                            // Marquer le message comme lu
                            $message->setFlag('seen');
                            
                            continue;
                        }
                    }
                    
                    // Recherche ou création de l'utilisateur
                    $user = $this->findOrCreateUser($fromAddress, $fromName);
                    
                    if (!$user) {
                        $summary['skipped']++;
                        $summary['details'][] = "Email ignoré (adresse invalide): {$subject}";
                        continue;
                    }
                    
                    // Création du nouveau ticket
                    $ticket = DB::transaction(function () use ($user, $subject, $body, $emailId) {
                        $ticket = Ticket::create([
                            'subject' => $subject,
                            'content' => $body,
                            'status' => 'ouvert',
                            'client_id' => $user->id,
                            'email_id' => $emailId,
                            'email_subject' => $subject,
                        ]);
                        
                        // Application des workflows
                        $this->applyWorkflows($ticket, $subject, $body);
                        
                        return $ticket;
                    });
                    
                    // Traitement des pièces jointes
                    $this->processAttachments($message, $ticket->id);
                    
                    $summary['new_tickets']++;
                    $summary['details'][] = "Nouveau ticket #{$ticket->id} créé: {$subject}";
                    
                    // Marquer le message comme lu
                    $message->setFlag('seen');
                    
                } catch (\Exception $e) {
                    Log::error('Erreur lors du traitement d\'un email', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'subject' => $message->getSubject() ?? 'Inconnu',
                    ]);
                    
                    $summary['errors']++;
                    $summary['details'][] = "Erreur: " . $e->getMessage() . " (Sujet: " . ($message->getSubject() ?? 'Inconnu') . ")";
                }
            }
            
            // Fermeture de la connexion
            $client->disconnect();
            
        } catch (ConnectionFailedException|InvalidParameterException $e) {
            Log::error('Erreur de connexion au serveur IMAP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $summary['errors']++;
            $summary['details'][] = "Erreur de connexion: " . $e->getMessage();
        }
        
        return $summary;
    }
    
    /**
     * Extrait le contenu texte d'un message
     *
     * @param \Webklex\PHPIMAP\Message $message
     * @return string
     */
    private function extractTextFromMessage($message): string
    {
        // Priorité: texte HTML puis texte brut
        if ($message->hasHTMLBody()) {
            return $message->getHTMLBody();
        }
        
        return $message->getTextBody();
    }
    
    /**
     * Trouve ou crée un utilisateur à partir d'une adresse email
     *
     * @param string|null $email
     * @param string|null $name
     * @return User|null
     */
    private function findOrCreateUser(?string $email, ?string $name): ?User
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Trouver le rôle client
            $clientRole = \App\Models\Role::where('name', 'client')->first();
            
            // Trouver la société correspondant au domaine de l'email
            $domain = substr($email, strpos($email, '@') + 1);
            $company = \App\Models\Company::where('email_domain', $domain)->first();
            
            // Créer le nouvel utilisateur
            $user = User::create([
                'name' => $name ?: explode('@', $email)[0],
                'email' => $email,
                'password' => bcrypt(Str::random(16)),
                'role_id' => $clientRole?->id,
                'company_id' => $company?->id,
            ]);
        }
        
        return $user;
    }
    
    /**
     * Applique les workflows au ticket
     *
     * @param Ticket $ticket
     * @param string $subject
     * @param string $content
     * @return void
     */
    private function applyWorkflows(Ticket $ticket, string $subject, string $content): void
    {
        // Récupération des workflows actifs
        $workflows = Workflow::where('is_active', true)->get();
        
        foreach ($workflows as $workflow) {
            $matched = false;
            
            // Vérifier la correspondance selon le type
            if ($workflow->match_type === 'subject' || $workflow->match_type === 'both') {
                if ($this->textContainsKeyword($subject, $workflow->keyword, $workflow->is_case_sensitive)) {
                    $matched = true;
                }
            }
            
            if (!$matched && ($workflow->match_type === 'body' || $workflow->match_type === 'both')) {
                if ($this->textContainsKeyword($content, $workflow->keyword, $workflow->is_case_sensitive)) {
                    $matched = true;
                }
            }
            
            if ($matched) {
                // Associer le label au ticket
                $ticket->labels()->attach($workflow->label_id, [
                    'is_automatic' => true,
                    'workflow_id' => $workflow->id,
                ]);
                
                // Envoyer les notifications par email si configurées
                $notificationEmails = $workflow->getNotificationEmailsArray();
                
                if (!empty($notificationEmails)) {
                    foreach ($notificationEmails as $email) {
                        $this->sendNotificationEmail($email, $ticket, $workflow);
                    }
                }
                
                // Assigner le ticket aux agents qui ont ce label
                $agents = User::whereHas('role', function (Builder $query) {
                    $query->where('name', 'agent');
                })->whereHas('labels', function (Builder $query) use ($workflow) {
                    $query->where('id', $workflow->label_id);
                })->get();
                
                if ($agents->count() === 1) {
                    // Si un seul agent est trouvé, on lui assigne automatiquement le ticket
                    $ticket->update(['assigned_agent_id' => $agents->first()->id]);
                }
            }
        }
    }
    
    /**
     * Vérifie si un texte contient un mot-clé
     *
     * @param string $text
     * @param string $keyword
     * @param bool $caseSensitive
     * @return bool
     */
    private function textContainsKeyword(string $text, string $keyword, bool $caseSensitive): bool
    {
        if (!$caseSensitive) {
            return str_contains(strtolower($text), strtolower($keyword));
        }
        
        return str_contains($text, $keyword);
    }
    
    /**
     * Envoie un email de notification
     *
     * @param string $email
     * @param Ticket $ticket
     * @param Workflow $workflow
     * @return void
     */
    private function sendNotificationEmail(string $email, Ticket $ticket, Workflow $workflow): void
    {
        try {
            Mail::raw(
                "Un nouveau ticket #{$ticket->id} a été créé et correspond au workflow '{$workflow->name}'.\n\n" .
                "Sujet: {$ticket->subject}\n" .
                "Client: {$ticket->client->name} <{$ticket->client->email}>\n\n" .
                "Vous recevez cette notification car votre adresse email est configurée dans les notifications du workflow.",
                function ($message) use ($email, $ticket, $workflow) {
                    $message->to($email)
                        ->subject("[Ticket #{$ticket->id}] Nouveau ticket correspondant au workflow '{$workflow->name}'");
                }
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi d\'une notification par email', [
                'error' => $e->getMessage(),
                'email' => $email,
                'ticket_id' => $ticket->id,
            ]);
        }
    }
    
    /**
     * Traite les pièces jointes d'un message
     *
     * @param \Webklex\PHPIMAP\Message $message
     * @param int|null $ticketId
     * @param int|null $responseId
     * @return void
     */
    private function processAttachments($message, ?int $ticketId = null, ?int $responseId = null): void
    {
        $attachments = $message->getAttachments();
        
        foreach ($attachments as $attachment) {
            $fileName = $attachment->getName();
            $content = $attachment->getContent();
            $mimeType = $attachment->getMimeType();
            $size = $attachment->getSize();
            
            // Vérification du type MIME
            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip', 'application/x-zip-compressed'
            ];
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                continue;
            }
            
            // Vérification de la taille (max 10 Mo)
            $maxSize = 10 * 1024 * 1024; // 10 Mo
            if ($size > $maxSize) {
                continue;
            }
            
            // Génération d'un nom de fichier unique
            $uniqueFileName = Str::uuid() . '_' . $fileName;
            
            // Chemin de stockage
            $path = 'tickets/attachments/' . $uniqueFileName;
            
            // Stockage du fichier
            Storage::disk('public')->put($path, $content);
            
            // Vérification si c'est une image
            $isImage = in_array($mimeType, [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
            ]);
            
            // Création de l'enregistrement dans la base de données
            Attachment::create([
                'ticket_id' => $ticketId,
                'response_id' => $responseId,
                'file_path' => $path,
                'file_name' => $uniqueFileName,
                'file_type' => $mimeType,
                'file_size' => round($size / 1024), // Taille en Ko
                'is_image' => $isImage,
                'original_name' => $fileName,
            ]);
        }
    }
}
