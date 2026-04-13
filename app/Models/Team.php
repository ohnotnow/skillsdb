<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'manager_id',
    ];

    // Relationships

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withTimestamps();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
