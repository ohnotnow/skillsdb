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
        $gaps = Skill::approved()
            ->withCount('users')
            ->having('users_count', '<=', $max_users)
            ->having('users_count', '>=', 1)
            ->orderBy('users_count')
            ->limit(15)
            ->get()
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name ?? 'Uncategorised',
                'users_count' => $s->users_count,
            ])
            ->toArray();

        $noOneSKills = Skill::approved()
            ->withCount('users')
            ->having('users_count', '=', 0)
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name ?? 'Uncategorised',
            ])
            ->toArray();

        return json_encode([
            'thin_coverage' => $gaps,
            'no_coverage' => $noOneSKills,
            'message' => 'Skills with thin coverage are opportunities - becoming proficient makes you valuable.',
        ], JSON_PRETTY_PRINT);
    }
}
