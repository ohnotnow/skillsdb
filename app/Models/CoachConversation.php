<?php

namespace App\Models;

use App\Enums\CoachMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoachConversation extends Model
{
    /** @use HasFactory<\Database\Factories\CoachConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mode',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'mode' => CoachMode::class,
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CoachMessage::class)->oldest();
    }

    // Scopes

    public function scopePersonal(Builder $query): Builder
    {
        return $query->where('mode', CoachMode::Personal);
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('mode', CoachMode::Team)->where('team_id', $teamId);
    }
}
