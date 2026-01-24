<?php

namespace App\Services\SkillsCoach;

use App\Models\Skill;
use App\Models\User;

class SystemPrompt
{
    /**
     * Build the complete system prompt for a user.
     */
    public function build(User $user): string
    {
        return implode("\n\n", [
            $this->personality(),
            $this->userContext($user),
            $this->teamContext(),
        ]);
    }

    /**
     * The coach's personality and mission - static, doesn't change.
     */
    protected function personality(): string
    {
        return view('prompts.skills-coach.personality')->render();
    }

    /**
     * Dynamic context about the current user.
     */
    protected function userContext(User $user): string
    {
        $user->load('skills');

        $skillSummary = $user->skills->isEmpty()
            ? 'No skills recorded yet.'
            : $user->skills->map(fn ($s) => "{$s->name} ({$s->pivot->level->label()})")->join(', ');

        $distribution = $user->getSkillDistribution();
        $stale = $user->hasStaleSkills() ? "Note: Skills haven't been updated in over 4 weeks." : '';

        return <<<PROMPT
## Current User

Name: {$user->full_name}
Skills ({$distribution['total']} total): {$skillSummary}
Distribution: {$distribution['high']} High, {$distribution['medium']} Medium, {$distribution['low']} Low
{$stale}
PROMPT;
    }

    /**
     * Dynamic context about the wider team.
     */
    protected function teamContext(): string
    {
        $trending = Skill::getTrendingSkills(5)
            ->pluck('name')
            ->join(', ') ?: 'None recently';

        $totalUsers = User::where('is_staff', true)->count();
        $totalSkills = Skill::approved()->count();

        return <<<PROMPT
## Team Context

Team size: {$totalUsers} staff members
Available skills: {$totalSkills} approved skills in the system
Trending skills (recently added/levelled up): {$trending}
PROMPT;
    }
}
