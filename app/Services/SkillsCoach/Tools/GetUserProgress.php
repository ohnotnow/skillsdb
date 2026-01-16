<?php

namespace App\Services\SkillsCoach\Tools;

use App\Services\SkillsCoach\CoachContext;
use Prism\Prism\Tool;

class GetUserProgress extends Tool
{
    public function __construct(
        protected CoachContext $context
    ) {
        $this
            ->as('get_user_progress')
            ->for('Get the current user\'s skill progress over time - useful for understanding momentum')
            ->withNumberParameter('months', 'Number of months to look back (default 6)')
            ->using($this);
    }

    public function __invoke(int $months = 6): string
    {
        $user = $this->context->getUserOrFail();

        $progress = $user->getSkillsOverTimeFromHistory($months);

        $firstPoints = $progress[0]['points'] ?? 0;
        $lastPoints = end($progress)['points'] ?? 0;
        $growth = $lastPoints - $firstPoints;

        return json_encode([
            'user' => $user->full_name,
            'months_analysed' => $months,
            'progress' => $progress,
            'summary' => [
                'starting_points' => $firstPoints,
                'current_points' => $lastPoints,
                'growth' => $growth,
                'momentum' => $growth > 0 ? 'growing' : ($growth < 0 ? 'declining' : 'stable'),
            ],
        ], JSON_PRETTY_PRINT);
    }
}
