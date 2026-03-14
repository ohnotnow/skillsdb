<?php

namespace App\Ai\Tools\PersonalTools;

use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetUserProgress implements Tool
{
    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return "Get the current user's skill progress over time - useful for understanding momentum";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'months' => $schema->integer()->min(1)->max(24),
        ];
    }

    public function handle(Request $request): string
    {
        $months = $request['months'] ?? 6;
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
