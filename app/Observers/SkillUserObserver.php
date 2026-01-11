<?php

namespace App\Observers;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\SkillHistory;
use App\Models\SkillUser;

class SkillUserObserver
{
    public function created(SkillUser $skillUser): void
    {
        SkillHistory::create([
            'user_id' => $skillUser->user_id,
            'skill_id' => $skillUser->skill_id,
            'event_type' => SkillHistoryEvent::Added,
            'old_level' => null,
            'new_level' => $skillUser->level->value,
        ]);
    }

    public function updated(SkillUser $skillUser): void
    {
        if (! $skillUser->wasChanged('level')) {
            return;
        }

        $original = $skillUser->getOriginal('level');
        $oldLevel = $original instanceof SkillLevel ? $original->value : (int) $original;
        $newLevel = $skillUser->level->value;

        SkillHistory::create([
            'user_id' => $skillUser->user_id,
            'skill_id' => $skillUser->skill_id,
            'event_type' => $newLevel > $oldLevel
                ? SkillHistoryEvent::LevelledUp
                : SkillHistoryEvent::LevelledDown,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
        ]);
    }

    public function deleted(SkillUser $skillUser): void
    {
        SkillHistory::create([
            'user_id' => $skillUser->user_id,
            'skill_id' => $skillUser->skill_id,
            'event_type' => SkillHistoryEvent::Removed,
            'old_level' => $skillUser->level->value,
            'new_level' => null,
        ]);
    }
}
