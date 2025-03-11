<?php

namespace App\Filament\Pages;

use App\Models\Label;
use App\Models\Ticket;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as StatsOverviewWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected function getHeaderWidgets(): array
    {
        return [
            TicketStats::class,
            TicketChartWidget::class,
        ];
    }
}

class TicketStats extends StatsOverviewWidget\Widget
{
    protected function getStats(): array
    {
        $userCount = User::count();
        $clientCount = User::whereHas('role', fn($q) => $q->where('name', 'client'))->count();
        $agentCount = User::whereHas('role', fn($q) => $q->where('name', 'agent'))->count();

        $ticketCount = Ticket::count();
        $openTickets = Ticket::where('status', 'ouvert')->count();
        $pendingTickets = Ticket::where('status', 'en_attente')->count();
        $resolvedTickets = Ticket::where('status', 'résolu')->count();
        $closedTickets = Ticket::where('status', 'fermé')->count();
        
        $unassignedTickets = Ticket::whereNull('assigned_agent_id')
            ->whereIn('status', ['ouvert', 'en_attente'])
            ->count();
        
        $averageResolutionTime = DB::table('tickets')
            ->whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_time'))
            ->first()
            ->avg_resolution_time ?? 0;
        
        $averageResolutionTime = round($averageResolutionTime, 1);
        
        return [
            Stat::make(__('Total Tickets'), $ticketCount)
                ->description(__('Tous les tickets'))
                ->color('primary'),
            Stat::make(__('Tickets ouverts'), $openTickets)
                ->description(__('En attente de traitement'))
                ->color('danger'),
            Stat::make(__('Tickets en attente'), $pendingTickets)
                ->description(__('En attente de réponse client'))
                ->color('warning'),
            Stat::make(__('Non assignés'), $unassignedTickets)
                ->description(__('Tickets sans agent assigné'))
                ->color($unassignedTickets > 0 ? 'danger' : 'success'),
            Stat::make(__('Temps moyen résolution'), $averageResolutionTime . ' h')
                ->description(__('Pour les tickets résolus'))
                ->color('success'),
            Stat::make(__('Utilisateurs'), $userCount)
                ->description(__('Dont {0} clients et {1} agents', [$clientCount, $agentCount]))
                ->color('primary'),
        ];
    }
}

class TicketChartWidget extends StatsOverviewWidget\Widget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Tickets par statut
        $statusDistribution = [];
        foreach (['ouvert', 'en_attente', 'résolu', 'fermé'] as $status) {
            $statusDistribution[$status] = Ticket::where('status', $status)->count();
        }
        
        // Tickets créés cette semaine
        $today = Carbon::now();
        $weekStart = Carbon::now()->startOfWeek();
        
        $ticketsThisWeek = Ticket::whereBetween('created_at', [$weekStart, $today])->count();
        $ticketsLastWeek = Ticket::whereBetween('created_at', [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek()
        ])->count();
        
        $weekChange = $ticketsLastWeek > 0 
            ? round((($ticketsThisWeek - $ticketsLastWeek) / $ticketsLastWeek) * 100, 1) 
            : 0;
        
        // Tickets résolus cette semaine
        $resolvedThisWeek = Ticket::whereBetween('resolved_at', [$weekStart, $today])->count();
        $resolvedLastWeek = Ticket::whereBetween('resolved_at', [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek()
        ])->count();
        
        $resolvedChange = $resolvedLastWeek > 0
            ? round((($resolvedThisWeek - $resolvedLastWeek) / $resolvedLastWeek) * 100, 1)
            : 0;
        
        // Top labels
        $topLabels = Label::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($label) {
                return $label->name . ' (' . $label->tickets_count . ')';
            })
            ->implode(', ');
        
        return [
            Stat::make(__('Distribution par statut'), __('Ouvert: {0} | En attente: {1} | Résolu: {2} | Fermé: {3}', [
                $statusDistribution['ouvert'],
                $statusDistribution['en_attente'],
                $statusDistribution['résolu'],
                $statusDistribution['fermé'],
            ]))
                ->chart([
                    $statusDistribution['ouvert'],
                    $statusDistribution['en_attente'],
                    $statusDistribution['résolu'],
                    $statusDistribution['fermé'],
                ])
                ->color('primary'),
            
            Stat::make(__('Tickets créés (semaine)'), $ticketsThisWeek)
                ->description($weekChange >= 0 
                    ? __('Hausse de {0}% vs semaine précédente', [$weekChange]) 
                    : __('Baisse de {0}% vs semaine précédente', [abs($weekChange)]))
                ->chart([$ticketsLastWeek, $ticketsThisWeek])
                ->color($weekChange > 0 ? 'danger' : 'success'),
            
            Stat::make(__('Tickets résolus (semaine)'), $resolvedThisWeek)
                ->description($resolvedChange >= 0 
                    ? __('Hausse de {0}% vs semaine précédente', [$resolvedChange]) 
                    : __('Baisse de {0}% vs semaine précédente', [abs($resolvedChange)]))
                ->chart([$resolvedLastWeek, $resolvedThisWeek])
                ->color($resolvedChange > 0 ? 'success' : 'danger'),
                
            Stat::make(__('Top labels'), $topLabels)
                ->description(__('Les catégories les plus utilisées'))
                ->color('warning'),
        ];
    }
}
