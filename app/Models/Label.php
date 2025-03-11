<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Label extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'color',
    ];

    /**
     * Relation avec les tickets ayant ce label
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_label')
            ->withPivot(['is_automatic', 'workflow_id'])
            ->withTimestamps();
    }

    /**
     * Relation avec les agents assignés à ce label
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agent_label');
    }

    /**
     * Relation avec les workflows qui utilisent ce label
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
