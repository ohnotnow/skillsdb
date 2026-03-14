<?php

namespace App\Ai\Tools\PersonalTools;

use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetUserProfile implements Tool
{
    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return "Get the current user's skills, levels, distribution, and recent activity";
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user = $this->context->getUserOrFail();
        $user->load(['skills', 'skillHistory' => fn ($q) => $q->with('skill')->limit(10)]);

        $skills = $user->skills->map(fn ($s) => [
            'name' => $s->name,
            'level' => $s->pivot->level->label(),
            'category' => $s->category?->name ?? 'Uncategorised',
        ])->toArray();

        $distribution = $user->getSkillDistribution();

        $recentHistory = $user->skillHistory->take(5)->map(fn ($h) => [
            'skill' => $h->skill->name,
            'event' => $h->event_type->label(),
            'date' => $h->created_at->diffForHumans(),
        ])->toArray();

        return json_encode([
            'name' => $user->full_name,
            'total_skills' => $distribution['total'],
            'distribution' => [
                'high' => $distribution['high'],
                'medium' => $distribution['medium'],
                'low' => $distribution['low'],
            ],
            'skills' => $skills,
            'recent_activity' => $recentHistory,
            'last_updated' => $user->last_updated_skills_at?->diffForHumans() ?? 'never',
            'stale' => $user->hasStaleSkills(),
        ], JSON_PRETTY_PRINT);
    }
}
