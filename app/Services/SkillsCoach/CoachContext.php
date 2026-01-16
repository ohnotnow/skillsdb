<?php

namespace App\Services\SkillsCoach;

use App\Models\User;

/**
 * Holds the current user context for coach tools.
 * Bound as a singleton and set before each chat request.
 */
class CoachContext
{
    protected ?User $user = null;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserOrFail(): User
    {
        return $this->user ?? auth()->user();
    }
}
