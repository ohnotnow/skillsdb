<?php

namespace App\Ai\Tools\TeamTools;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FindMentoringPairs implements Tool
{
    use HandlesContactability;

    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'Find who could teach whom. Connect High-level experts with people who want to learn. The heart of the coach - people helping people.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'skill_name' => $schema->string(),
            'mentee_name' => $schema->string(),
            'mentor_name' => $schema->string(),
        ];
    }

    public function handle(Request $request): string
    {
        $skill_name = $request['skill_name'] ?? null;
        $mentee_name = $request['mentee_name'] ?? null;
        $mentor_name = $request['mentor_name'] ?? null;
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        $team->load('members.skills.category', 'members.skillHistory');
        $members = $team->members;

        if ($skill_name) {
            return $this->findPairsForSkill($skill_name, $members);
        }

        if ($mentee_name) {
            return $this->findMentorsForPerson($mentee_name, $members);
        }

        if ($mentor_name) {
            return $this->findMenteesForPerson($mentor_name, $members);
        }

        return $this->findGeneralOpportunities($members);
    }

    protected function findPairsForSkill(string $skillName, $members): string
    {
        $skill = $this->findSkillByName($skillName);

        if (! $skill) {
            $suggestions = $this->findSimilarSkills($skillName);

            return json_encode([
                'exact_match' => false,
                'searched_for' => $skillName,
                'suggestions' => $suggestions,
                'hint' => 'No exact skill match. Try one of these skills.',
            ]);
        }

        $experts = $members->filter(function ($m) use ($skill) {
            $userSkill = $m->skills->find($skill->id);

            return $userSkill && $userSkill->pivot->level === SkillLevel::High;
        });

        $learners = $members->filter(function ($m) use ($skill) {
            $userSkill = $m->skills->find($skill->id);

            return ! $userSkill || $userSkill->pivot->level->value < SkillLevel::High->value;
        });

        $potentialPairs = $experts->map(function ($expert) use ($skill, $learners) {
            $couldMentor = $learners
                ->filter(fn ($l) => $l->id !== $expert->id)
                ->map(function ($learner) use ($skill) {
                    $learnerSkill = $learner->skills->find($skill->id);
                    $learnerLevel = $learnerSkill?->pivot->level;
                    $gap = $learnerLevel ? (SkillLevel::High->value - $learnerLevel->value) : 3;

                    return $this->formatPersonWithContactability($learner, [
                        'level' => $learnerLevel?->label() ?? 'No skill yet',
                        'gap' => $gap,
                    ]);
                })
                ->sortByDesc('gap')
                ->values()
                ->toArray();

            return [
                'mentor' => $this->formatPersonWithContactability($expert, ['level' => 'High']),
                'could_mentor' => $couldMentor,
            ];
        })->values()->toArray();

        $withoutSkill = $members
            ->filter(fn ($m) => ! $m->skills->contains('id', $skill->id))
            ->pluck('full_name')
            ->toArray();

        $suggestion = $this->generateSkillSuggestion($skill->name, $experts->count(), $learners->count());

        return json_encode([
            'skill' => $skill->name,
            'potential_pairs' => $potentialPairs,
            'people_without_skill' => $withoutSkill,
            'suggestion' => $suggestion,
        ], JSON_PRETTY_PRINT);
    }

    protected function findMentorsForPerson(string $personName, $members): string
    {
        $person = $this->findMemberByName($personName, $members);

        if (! $person) {
            $suggestions = $members->pluck('full_name')->toArray();

            return json_encode([
                'exact_match' => false,
                'searched_for' => $personName,
                'suggestions' => $suggestions,
                'hint' => 'Could not find that person. Here are the team members.',
            ]);
        }

        $personSkillIds = $person->skills->pluck('id')->toArray();
        $personSkillLevels = $person->skills->mapWithKeys(fn ($s) => [$s->id => $s->pivot->level]);

        $mentorOpportunities = $members
            ->filter(fn ($m) => $m->id !== $person->id)
            ->flatMap(function ($potential) use ($personSkillIds, $personSkillLevels) {
                return $potential->skills
                    ->filter(fn ($s) => $s->pivot->level === SkillLevel::High)
                    ->filter(function ($s) use ($personSkillIds, $personSkillLevels) {
                        if (! in_array($s->id, $personSkillIds)) {
                            return true;
                        }

                        return $personSkillLevels[$s->id]->value < SkillLevel::High->value;
                    })
                    ->map(fn ($s) => [
                        'skill' => $s->name,
                        'mentor' => $this->formatPersonWithContactability($potential),
                        'your_level' => $personSkillLevels[$s->id]?->label() ?? 'No skill yet',
                    ]);
            })
            ->groupBy('skill')
            ->map(function ($group, $skill) {
                $mentors = $group->pluck('mentor')
                    ->unique(fn ($m) => $m['name'])
                    ->values()
                    ->toArray();

                return [
                    'skill' => $skill,
                    'potential_mentors' => $mentors,
                    'your_level' => $group->first()['your_level'],
                ];
            })
            ->values()
            ->toArray();

        return json_encode([
            'for_person' => $person->full_name,
            'mentoring_opportunities' => $mentorOpportunities,
            'count' => count($mentorOpportunities),
        ], JSON_PRETTY_PRINT);
    }

    protected function findMenteesForPerson(string $personName, $members): string
    {
        $person = $this->findMemberByName($personName, $members);

        if (! $person) {
            $suggestions = $members->pluck('full_name')->toArray();

            return json_encode([
                'exact_match' => false,
                'searched_for' => $personName,
                'suggestions' => $suggestions,
                'hint' => 'Could not find that person. Here are the team members.',
            ]);
        }

        $expertSkills = $person->skills->filter(fn ($s) => $s->pivot->level === SkillLevel::High);

        $mentoringOpportunities = $expertSkills->map(function ($skill) use ($members, $person) {
            $potentialMentees = $members
                ->filter(fn ($m) => $m->id !== $person->id)
                ->filter(function ($m) use ($skill) {
                    $theirSkill = $m->skills->find($skill->id);

                    return ! $theirSkill || $theirSkill->pivot->level->value < SkillLevel::High->value;
                })
                ->map(function ($m) use ($skill) {
                    $theirSkill = $m->skills->find($skill->id);

                    return $this->formatPersonWithContactability($m, [
                        'level' => $theirSkill?->pivot->level->label() ?? 'No skill yet',
                    ]);
                })
                ->sortBy(fn ($m) => $m['level'] === 'No skill yet' ? 0 : SkillLevel::tryFrom($m['level'])?->value ?? 0)
                ->values()
                ->toArray();

            return [
                'skill' => $skill->name,
                'your_level' => 'High',
                'potential_mentees' => $potentialMentees,
            ];
        })->values()->toArray();

        return json_encode([
            'mentor' => $person->full_name,
            'could_teach' => $mentoringOpportunities,
            'count' => count($mentoringOpportunities),
        ], JSON_PRETTY_PRINT);
    }

    protected function findGeneralOpportunities($members): array|string
    {
        $opportunities = collect();

        $allSkills = $members->flatMap(fn ($m) => $m->skills)->unique('id');

        foreach ($allSkills as $skill) {
            $experts = $members->filter(function ($m) use ($skill) {
                $userSkill = $m->skills->find($skill->id);

                return $userSkill && $userSkill->pivot->level === SkillLevel::High;
            });

            $learners = $members->filter(function ($m) use ($skill) {
                $userSkill = $m->skills->find($skill->id);

                return $userSkill && $userSkill->pivot->level->value < SkillLevel::High->value;
            });

            if ($experts->count() === 1 && $learners->isNotEmpty()) {
                $expert = $experts->first();
                $opportunities->push([
                    'skill' => $skill->name,
                    'mentor' => $this->formatPersonWithContactability($expert, ['level' => 'High']),
                    'potential_mentees' => $learners->map(fn ($l) => $this->formatPersonWithContactability($l, [
                        'level' => $l->skills->find($skill->id)->pivot->level->label(),
                    ]))->values()->toArray(),
                    'why' => 'Single expert with eager learners',
                ]);
            }
        }

        $recentLearners = $this->findRecentLearners($members);

        return json_encode([
            'high_value_opportunities' => $opportunities->take(5)->toArray(),
            'recent_learners' => $recentLearners,
            'suggestion' => $opportunities->isEmpty()
                ? 'No obvious mentoring gaps found. Consider using get_team_overview to see who might benefit from growth opportunities.'
                : 'These are skills with a single expert - good candidates for knowledge sharing.',
        ], JSON_PRETTY_PRINT);
    }

    protected function findRecentLearners($members): array
    {
        $sixtyDaysAgo = now()->subDays(60);

        return $members
            ->filter(function ($m) use ($sixtyDaysAgo) {
                return $m->skillHistory
                    ->where('event_type', SkillHistoryEvent::Added)
                    ->where('created_at', '>=', $sixtyDaysAgo)
                    ->isNotEmpty();
            })
            ->map(function ($m) use ($sixtyDaysAgo) {
                $recentSkills = $m->skillHistory
                    ->where('event_type', SkillHistoryEvent::Added)
                    ->where('created_at', '>=', $sixtyDaysAgo)
                    ->map(fn ($h) => $h->skill->name ?? 'Unknown')
                    ->unique()
                    ->values()
                    ->toArray();

                return $this->formatPersonWithContactability($m, [
                    'recently_started' => $recentSkills,
                ]);
            })
            ->filter(fn ($m) => ! empty($m['recently_started']))
            ->values()
            ->toArray();
    }

    protected function getContext(): CoachContext
    {
        return $this->context;
    }

    protected function findMemberByName(string $name, $members)
    {
        $nameLower = strtolower(trim($name));

        return $members->first(function ($m) use ($nameLower) {
            return strtolower($m->full_name) === $nameLower
                || strtolower($m->forenames) === $nameLower
                || strtolower($m->surname) === $nameLower
                || str_contains(strtolower($m->full_name), $nameLower);
        });
    }

    protected function findSkillByName(string $name): ?Skill
    {
        return Skill::byName($name)->approved()->first();
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

    protected function generateSkillSuggestion(string $skillName, int $expertCount, int $learnerCount): string
    {
        if ($expertCount === 0) {
            $learnerNote = $learnerCount > 0 ? " ({$learnerCount} people are learning)" : '';

            return "No {$skillName} experts in the team yet{$learnerNote}. Consider external training or finding someone outside the team.";
        }

        if ($expertCount === 1) {
            $learnerNote = $learnerCount > 0 ? " {$learnerCount} people could benefit" : '';

            return "There's only one {$skillName} expert.{$learnerNote} Worth asking if they'd enjoy helping others get started?";
        }

        return "Good {$skillName} coverage with {$expertCount} experts. Plenty of options for mentoring.";
    }
}
