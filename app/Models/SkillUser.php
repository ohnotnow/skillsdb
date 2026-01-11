<?php

namespace App\Models;

use App\Enums\SkillLevel;
use App\Observers\SkillUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy(SkillUserObserver::class)]
class SkillUser extends Pivot
{
    protected $table = 'skill_user';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'level' => SkillLevel::class,
        ];
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
