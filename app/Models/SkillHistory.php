<?php

namespace App\Models;

use App\Enums\SkillHistoryEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillHistory extends Model
{
    /** @use HasFactory<\Database\Factories\SkillHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'skill_id',
        'event_type',
        'old_level',
        'new_level',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SkillHistoryEvent::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SkillHistory $history) {
            $history->created_at ??= now();
        });
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
