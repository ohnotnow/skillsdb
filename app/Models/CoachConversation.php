<?php

namespace App\Models;

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
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CoachMessage::class)->oldest();
    }
}
