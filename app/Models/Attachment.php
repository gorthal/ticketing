<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'response_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_image',
        'original_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_image' => 'boolean',
    ];

    /**
     * Relation avec le ticket parent
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relation avec la réponse parent
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(TicketResponse::class, 'response_id');
    }

    /**
     * Retourne l'URL complète du fichier
     */
    public function getFileUrl(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Vérifie si le fichier est une image affichable
     */
    public function isDisplayableImage(): bool
    {
        return $this->is_image && in_array(
            strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
        );
    }

    /**
     * Retourne la taille du fichier formatée
     */
    public function getFormattedSize(): string
    {
        $size = $this->file_size;
        
        if ($size < 1024) {
            return "{$size} Ko";
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 2) . " Mo";
        } else {
            return round($size / (1024 * 1024), 2) . " Go";
        }
    }
}
