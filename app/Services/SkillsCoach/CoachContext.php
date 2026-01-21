<?php

namespace App\Services\SkillsCoach;

use App\Enums\CoachMode;
use App\Models\Team;
use App\Models\User;

/**
 * Holds the current user context for coach tools.
 * Bound as a singleton and set before each chat request.
 */
class CoachContext
{
    protected ?User $user = null;

    protected ?Team $team = null;

    protected CoachMode $mode = CoachMode::Personal;

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

    public function setTeam(Team $team): void
    {
        $this->team = $team;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setMode(CoachMode $mode): void
    {
        $this->mode = $mode;
    }

    public function getMode(): CoachMode
    {
        return $this->mode;
    }

    public function isTeamMode(): bool
    {
        return $this->mode === CoachMode::Team;
    }
}
