<?php

namespace App\Services\SkillsCoach\Tools;

use App\Models\Skill;
use App\Models\SkillHistory;
use App\Services\SkillsCoach\CoachContext;
use Prism\Prism\Tool;

class GetSkillJourney extends Tool
{
    public function __construct(
        protected CoachContext $context
    ) {
        $this
            ->as('get_skill_journey')
            ->for('Get the full history of the current user\'s journey with a specific skill')
            ->withStringParameter('skill_name', 'The name of the skill to get journey for')
            ->using($this);
    }

    public function __invoke(string $skill_name): string
    {
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
                'old_level' => $h->old_level ? \App\Enums\SkillLevel::from($h->old_level)->label() : null,
                'new_level' => $h->new_level ? \App\Enums\SkillLevel::from($h->new_level)->label() : null,
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
