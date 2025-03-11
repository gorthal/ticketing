<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ArchiveTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:archive {--dry-run : Afficher les tickets qui seraient archivés sans les archiver}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive les tickets résolus depuis plus de 3 jours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Récupérer le nombre de jours depuis la variable d'environnement (par défaut 3)
        $archiveDays = env('TICKET_AUTO_ARCHIVE_DAYS', 3);
        
        // Calculer la date limite
        $thresholdDate = Carbon::now()->subDays($archiveDays);
        
        // Récupérer les tickets à archiver
        $ticketsQuery = Ticket::where('status', 'résolu')
            ->where('is_archived', false)
            ->where('resolved_at', '<=', $thresholdDate);
        
        $count = $ticketsQuery->count();
        
        if ($count === 0) {
            $this->info('Aucun ticket à archiver.');
            return Command::SUCCESS;
        }
        
        $this->info("{$count} ticket(s) résolu(s) depuis plus de {$archiveDays} jours trouvé(s).");
        
        if ($this->option('dry-run')) {
            $this->info('Mode simulation activé. Aucun ticket ne sera réellement archivé.');
            
            $tickets = $ticketsQuery->get();
            $this->table(
                ['ID', 'Sujet', 'Client', 'Résolu le'],
                $tickets->map(function ($ticket) {
                    return [
                        $ticket->id,
                        $ticket->subject,
                        $ticket->client->name,
                        $ticket->resolved_at->format('Y-m-d H:i:s'),
                    ];
                })
            );
            
            return Command::SUCCESS;
        }
        
        // Archiver les tickets
        $ticketsQuery->update(['is_archived' => true]);
        
        $this->info("{$count} ticket(s) archivé(s) avec succès.");
        
        return Command::SUCCESS;
    }
}
