<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workflow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'keyword',
        'label_id',
        'match_type',
        'is_case_sensitive',
        'notification_emails',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_case_sensitive' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec le label associé au workflow
     */
    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    /**
     * Récupère la liste des emails pour les notifications
     */
    public function getNotificationEmailsArray(): array
    {
        if (empty($this->notification_emails)) {
            return [];
        }
        
        return array_map('trim', explode(',', $this->notification_emails));
    }

    /**
     * Vérifie si un texte correspond au mot-clé du workflow
     */
    public function matchesText(string $text): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->is_case_sensitive) {
            return str_contains($text, $this->keyword);
        } else {
            return str_contains(strtolower($text), strtolower($this->keyword));
        }
    }
}
