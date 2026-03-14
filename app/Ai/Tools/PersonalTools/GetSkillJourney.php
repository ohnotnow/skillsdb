<?php

namespace App\Ai\Tools\PersonalTools;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillHistory;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetSkillJourney implements Tool
{
    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return "Get the full history of the current user's journey with a specific skill";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'skill_name' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $skill_name = $request['skill_name'];
        $user = $this->context->getUserOrFail();

        $skill = Skill::where('name', 'like', "%{$skill_name}%")->first();

        if (! $skill) {
            return json_encode([
                'found' => false,
                'message' => "No skill matching '{$skill_name}' found.",
            ]);
        }

        $history = SkillHistory::where('user_id', $user->id)
            ->where('skill_id', $skill->id)
            ->oldest()
            ->get()
            ->map(fn ($h) => [
                'event' => $h->event_type->label(),
                'old_level' => $h->old_level ? SkillLevel::from($h->old_level)->label() : null,
                'new_level' => $h->new_level ? SkillLevel::from($h->new_level)->label() : null,
                'date' => $h->created_at->format('j M Y'),
                'ago' => $h->created_at->diffForHumans(),
            ])
            ->toArray();

        $currentLevel = $user->getSkillLevel($skill);

        return json_encode([
            'skill' => $skill->name,
            'current_level' => $currentLevel?->label() ?? 'Not currently held',
            'journey' => $history,
            'total_events' => count($history),
        ], JSON_PRETTY_PRINT);
    }
}
