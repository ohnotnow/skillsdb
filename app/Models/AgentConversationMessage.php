<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(keyType: 'string', incrementing: false)]
#[Fillable('id', 'conversation_id', 'user_id', 'agent', 'role', 'content', 'meta', 'attachments', 'tool_calls', 'tool_results', 'usage')]
class AgentConversationMessage extends Model
{
    protected $attributes = [
        'meta' => '[]',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'attachments' => 'array',
            'tool_calls' => 'array',
            'tool_results' => 'array',
            'usage' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }
}
