<?php

namespace App\Ai\Tools\PersonalTools;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FindExperts implements Tool
{
    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'Find colleagues with High proficiency in a skill who can mentor or help';
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
        $currentUser = $this->context->getUserOrFail();

        $skill = Skill::where('name', 'like', "%{$skill_name}%")
            ->approved()
            ->first();

        if (! $skill) {
            return json_encode([
                'found' => false,
                'message' => "No skill matching '{$skill_name}' found in the system.",
            ]);
        }

        $experts = User::whereHas('skills', function ($query) use ($skill) {
            $query->where('skill_id', $skill->id)
                ->where('level', SkillLevel::High->value);
        })
            ->where('id', '!=', $currentUser->id)
            ->where('coach_contactable', true)
            ->where('is_staff', true)
            ->get()
            ->map(fn ($u) => [
                'name' => $u->full_name,
                'note' => $u->id === $currentUser->id ? '(this is you)' : null,
            ])
            ->filter(fn ($e) => $e['note'] === null)
            ->values()
            ->toArray();

        return json_encode([
            'skill' => $skill->name,
            'experts' => $experts,
            'count' => count($experts),
            'message' => count($experts) === 0
                ? "No experts found for {$skill->name} (or they've opted out of recommendations)."
                : null,
        ], JSON_PRETTY_PRINT);
    }
}
