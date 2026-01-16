<?php

namespace App\Models;

use App\Enums\CoachMessageRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachMessage extends Model
{
    /** @use HasFactory<\Database\Factories\CoachMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'coach_conversation_id',
        'role',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'role' => CoachMessageRole::class,
        ];
    }

    // Relationships

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CoachConversation::class, 'coach_conversation_id');
    }
}
