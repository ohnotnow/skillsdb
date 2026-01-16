<?php

namespace App\Services\SkillsCoach\Tools;

use App\Models\Skill;
use Prism\Prism\Tool;

class GetTeamGaps extends Tool
{
    public function __construct()
    {
        $this
            ->as('get_team_gaps')
            ->for('Find skills where the team has thin coverage - opportunities for someone to become valuable')
            ->withNumberParameter('max_users', 'Maximum number of users with the skill to be considered a gap (default 2)')
            ->using($this);
    }

    public function __invoke(int $max_users = 2): string
    {
        $allSkills = Skill::approved()->withCount('users')->get();

        $gaps = $allSkills
            ->filter(fn ($s) => $s->users_count >= 1 && $s->users_count <= $max_users)
            ->sortBy('users_count')
            ->take(15)
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name ?? 'Uncategorised',
                'users_count' => $s->users_count,
            ])
            ->values()
            ->toArray();

        $noOneSkills = $allSkills
            ->filter(fn ($s) => $s->users_count === 0)
            ->take(10)
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name ?? 'Uncategorised',
            ])
            ->values()
            ->toArray();

        return json_encode([
            'thin_coverage' => $gaps,
            'no_coverage' => $noOneSkills,
            'message' => 'Skills with thin coverage are opportunities - becoming proficient makes you valuable.',
        ], JSON_PRETTY_PRINT);
    }
}
