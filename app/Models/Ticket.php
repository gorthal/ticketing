<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subject',
        'content',
        'status',
        'client_id',
        'assigned_agent_id',
        'email_id',
        'email_subject',
        'is_archived',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_archived' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relation avec le client qui a créé le ticket
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Relation avec l'agent assigné au ticket
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Relation avec les réponses au ticket
     */
    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class);
    }

    /**
     * Relation avec les pièces jointes du ticket
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Relation avec les labels du ticket
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'ticket_label')
            ->withPivot(['is_automatic', 'workflow_id'])
            ->withTimestamps();
    }

    /**
     * Scope pour les tickets non archivés
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope pour les tickets archivés
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
