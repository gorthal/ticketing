<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email_domain',
    ];

    /**
     * Relation avec les utilisateurs de cette société
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Obtenir tous les tickets liés aux utilisateurs de cette société
     */
    public function tickets()
    {
        return Ticket::whereIn('client_id', $this->users()->pluck('id'));
    }
}
