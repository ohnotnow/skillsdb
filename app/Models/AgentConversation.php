<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentConversation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'title'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'conversation_id');
    }

    public function scopeForAgent(Builder $query, string $agentClass): Builder
    {
        return $query->whereHas('messages', fn (Builder $q) => $q->where('agent', $agentClass));
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->whereHas('messages', fn (Builder $q) => $q->whereJsonContains('meta->team_id', $teamId));
    }
}
