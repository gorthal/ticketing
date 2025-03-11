<?php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;

class FetchEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:fetch-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupérer les emails et créer des tickets';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService)
    {
        $this->info('Démarrage de la récupération des emails...');

        $summary = $emailService->fetchEmailsAndCreateTickets();

        $this->info('Récupération terminée.');
        $this->info('');
        
        $this->info('Résumé:');
        $this->info('--------');
        $this->info("Nouveaux tickets créés: {$summary['new_tickets']}");
        $this->info("Tickets mis à jour: {$summary['updated_tickets']}");
        $this->info("Emails ignorés: {$summary['skipped']}");
        $this->info("Erreurs: {$summary['errors']}");
        
        if (!empty($summary['details'])) {
            $this->info('');
            $this->info('Détails:');
            $this->info('--------');
            
            foreach ($summary['details'] as $detail) {
                $this->line(" - {$detail}");
            }
        }

        return Command::SUCCESS;
    }
}
