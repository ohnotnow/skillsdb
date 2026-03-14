<?php

namespace App\Ai\Tools\TeamTools;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SuggestTraining implements Tool
{
    use HandlesContactability;

    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'When human mentoring is not available - suggest external training approaches. This is a LAST RESORT - always check for internal experts first. A checkbox course is not the same as learning from someone experienced.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'skill_name' => $schema->string()->required(),
            'for_person' => $schema->string(),
        ];
    }

    public function handle(Request $request): string
    {
        $skill_name = $request['skill_name'];
        $for_person = $request['for_person'] ?? null;
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        $skill = $this->findSkillByName($skill_name);

        if (! $skill) {
            $suggestions = $this->findSimilarSkills($skill_name);

            return json_encode([
                'exact_match' => false,
                'searched_for' => $skill_name,
                'suggestions' => $suggestions,
                'hint' => 'No exact skill match. Try one of these skills.',
            ]);
        }

        $team->load('members.skills');
        $members = $team->members;

        $internalAssessment = $this->assessInternalExpertise($skill, $members);

        if ($internalAssessment['has_expert']) {
            return json_encode([
                'skill' => $skill->name,
                'internal_first' => [
                    'available' => true,
                    'experts' => $internalAssessment['experts'],
                    'suggestion' => "Actually, you have internal expertise! {$internalAssessment['experts'][0]['name']} is at High level. Have you considered asking them to teach? Internal mentoring is usually more effective than external courses.",
                ],
                'external_options' => [
                    'note' => 'External training not recommended when internal experts exist.',
                ],
            ], JSON_PRETTY_PRINT);
        }

        $forPersonNote = $for_person ? " for {$for_person}" : '';

        return json_encode([
            'skill' => $skill->name,
            'internal_first' => [
                'available' => false,
                'learners' => $internalAssessment['learners'],
                'note' => $this->buildInternalNote($internalAssessment),
            ],
            'external_options' => [
                'note' => "Since no internal expert is available{$forPersonNote}, external training might make sense.",
                'considerations' => [
                    'Look for hands-on labs, not just videos',
                    'University might have LinkedIn Learning or similar access',
                    'Check if vendor offers free training or certifications',
                    'Consider sending 2 people together - they can support each other',
                    'Conferences with workshops can be valuable for networking too',
                ],
                'suggested_approach' => 'Send one person for formal training, have them become the internal expert who teaches others. More sustainable than training everyone externally.',
            ],
            'alternative' => [
                'note' => 'Worth checking the wider organisation too',
                'suggestion' => 'Other departments might have experts - worth asking if someone could do a knowledge-sharing session? Check who else has this skill system-wide.',
            ],
            'philosophy' => 'Remember: a checkbox course with multiple choice questions is not the same as spending time with someone deeply experienced. If you do go external, build in time for the learner to practice and teach others.',
        ], JSON_PRETTY_PRINT);
    }

    protected function assessInternalExpertise($skill, $members): array
    {
        $experts = [];
        $learners = [];

        foreach ($members as $member) {
            $userSkill = $member->skills->find($skill->id);

            if (! $userSkill) {
                continue;
            }

            if ($userSkill->pivot->level === SkillLevel::High) {
                $experts[] = $this->formatPersonWithContactability($member);
            } else {
                $learners[] = $this->formatPersonWithContactability($member, [
                    'level' => $userSkill->pivot->level->label(),
                ]);
            }
        }

        return [
            'has_expert' => ! empty($experts),
            'experts' => $experts,
            'learners' => $learners,
        ];
    }

    protected function getContext(): CoachContext
    {
        return $this->context;
    }

    protected function buildInternalNote(array $assessment): string
    {
        if (empty($assessment['learners'])) {
            return 'No one in your team has this skill yet.';
        }

        $learnerCount = count($assessment['learners']);
        $names = collect($assessment['learners'])->take(2)->pluck('name')->implode(' and ');

        if ($learnerCount === 1) {
            return "{$names} is learning but not ready to teach yet.";
        }

        $more = $learnerCount > 2 ? ' (and '.($learnerCount - 2).' more)' : '';

        return "{$names}{$more} are learning but not ready to teach yet. They could learn together though!";
    }

    protected function findSkillByName(string $name): ?Skill
    {
        $nameLower = strtolower(trim($name));

        return Skill::whereRaw('LOWER(name) = ?', [$nameLower])
            ->approved()
            ->first();
    }

    protected function findSimilarSkills(string $searchTerm): array
    {
        $words = preg_split('/\s+/', strtolower(trim($searchTerm)));

        return Skill::approved()
            ->get()
            ->filter(function ($skill) use ($words) {
                $skillNameLower = strtolower($skill->name);
                foreach ($words as $word) {
                    if (strlen($word) >= 3 && str_contains($skillNameLower, $word)) {
                        return true;
                    }
                }

                return false;
            })
            ->take(6)
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name,
            ])
            ->values()
            ->toArray();
    }
}
