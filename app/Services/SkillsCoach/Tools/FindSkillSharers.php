<?php

namespace App\Services\SkillsCoach\Tools;

use App\Models\Skill;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use Prism\Prism\Tool;

class FindSkillSharers extends Tool
{
    public function __construct(
        protected CoachContext $context
    ) {
        $this
            ->as('find_skill_sharers')
            ->for('Find colleagues at any level who share a skill - potential study buddies or peers')
            ->withStringParameter('skill_name', 'The name of the skill to find sharers for')
            ->using($this);
    }

    public function __invoke(string $skill_name): string
    {
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

        $sharers = User::whereHas('skills', function ($query) use ($skill) {
            $query->where('skill_id', $skill->id);
        })
            ->where('id', '!=', $currentUser->id)
            ->where('coach_contactable', true)
            ->where('is_staff', true)
            ->with(['skills' => fn ($q) => $q->where('skill_id', $skill->id)])
            ->get()
            ->map(fn ($u) => [
                'name' => $u->full_name,
                'level' => $u->skills->first()->pivot->level->label(),
            ])
            /** @phpstan-ignore-next-line match.unhandled */
            ->sortByDesc(fn ($s) => match ($s['level']) {
                'High' => 3,
                'Medium' => 2,
                'Low' => 1,
            })
            ->values()
            ->toArray();

        return json_encode([
            'skill' => $skill->name,
            'sharers' => $sharers,
            'count' => count($sharers),
        ], JSON_PRETTY_PRINT);
    }
}
