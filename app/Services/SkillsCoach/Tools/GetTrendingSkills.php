<?php

namespace App\Services\SkillsCoach\Tools;

use App\Models\Skill;
use Prism\Prism\Tool;

class GetTrendingSkills extends Tool
{
    public function __construct()
    {
        $this
            ->as('get_trending_skills')
            ->for('Get skills that are trending - being added or levelled up recently by the team')
            ->withNumberParameter('limit', 'Maximum number of trending skills to return (default 10)')
            ->using($this);
    }

    public function __invoke(int $limit = 10): string
    {
        $trending = Skill::getTrendingSkills(limit: $limit);

        $results = $trending->map(fn ($s) => [
            'name' => $s->name,
            'category' => $s->category?->name ?? 'Uncategorised',
            'recent_additions' => $s->recent_count ?? 0,
        ])->toArray();

        return json_encode([
            'trending' => $results,
            'count' => count($results),
            'period' => 'last 30 days',
        ], JSON_PRETTY_PRINT);
    }
}
