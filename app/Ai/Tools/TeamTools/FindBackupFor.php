<?php

namespace App\Ai\Tools\TeamTools;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FindBackupFor implements Tool
{
    use HandlesContactability;

    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'Find who can cover for a person (if they are away/leaving) or who knows a specific skill. Answers: "If Jim is away, who covers?" or "Who knows Kubernetes?"';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'person_name' => $schema->string(),
            'skill_name' => $schema->string(),
        ];
    }

    public function handle(Request $request): string
    {
        $person_name = $request['person_name'] ?? null;
        $skill_name = $request['skill_name'] ?? null;
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        if (! $person_name && ! $skill_name) {
            return json_encode(['error' => 'Provide either person_name or skill_name']);
        }

        $team->load('members.skills.category');
        $members = $team->members;

        if ($person_name) {
            return $this->findBackupForPerson($person_name, $members);
        }

        return $this->findPeopleWithSkill($skill_name, $members);
    }

    protected function findBackupForPerson(string $personName, $members): string
    {
        $person = $this->findMemberByName($personName, $members);

        if (! $person) {
            $suggestions = $members->map(fn ($m) => $m->full_name)->values()->toArray();

            return json_encode([
                'exact_match' => false,
                'searched_for' => $personName,
                'suggestions' => $suggestions,
                'hint' => 'Could not find that person. Here are the team members.',
            ]);
        }

        $personSkills = $person->skills->map(fn ($s) => [
            'skill' => $s,
            'level' => $s->pivot->level,
        ]);

        $coverage = [];
        $fullyCovered = 0;
        $partiallyCovered = 0;
        $noBackup = 0;

        foreach ($personSkills as $skillData) {
            $skill = $skillData['skill'];
            $personLevel = $skillData['level'];

            $others = $members
                ->filter(fn ($m) => $m->id !== $person->id)
                ->filter(fn ($m) => $m->skills->contains('id', $skill->id))
                ->map(function ($m) use ($skill, $personLevel) {
                    $theirLevel = $m->skills->find($skill->id)->pivot->level;
                    $couldCover = $this->assessCoverage($theirLevel, $personLevel);

                    return $this->formatPersonWithContactability($m, [
                        'level' => $theirLevel->label(),
                        'could_cover' => $couldCover,
                    ]);
                })
                ->sortByDesc(fn ($o) => SkillLevel::tryFrom($o['level'])?->value ?? 0)
                ->values()
                ->toArray();

            $coverageEntry = [
                'skill' => $skill->name,
                "{$this->getFirstName($person)}_level" => $personLevel->label(),
                'others' => $others,
            ];

            if (empty($others)) {
                $coverageEntry['risk'] = "No backup - only {$this->getFirstName($person)} knows this";
                $noBackup++;
            } elseif (collect($others)->contains(fn ($o) => $o['could_cover'] === true)) {
                $fullyCovered++;
            } else {
                $partiallyCovered++;
            }

            $coverage[] = $coverageEntry;
        }

        $personFirstName = $this->getFirstName($person);
        $recommendation = $this->generateRecommendation($coverage, $personFirstName);

        return json_encode([
            'covering_for' => $person->full_name,
            "{$personFirstName}_skills" => $personSkills->map(fn ($s) => $s['skill']->name.' ('.$s['level']->label().')')->toArray(),
            'coverage' => $coverage,
            'summary' => [
                'fully_covered' => $fullyCovered,
                'partially_covered' => $partiallyCovered,
                'no_backup' => $noBackup,
            ],
            'recommendation' => $recommendation,
        ], JSON_PRETTY_PRINT);
    }

    protected function findPeopleWithSkill(string $skillName, $members): string
    {
        $skill = $this->findSkillByName($skillName);

        if (! $skill) {
            $suggestions = $this->findSimilarSkills($skillName);

            return json_encode([
                'exact_match' => false,
                'searched_for' => $skillName,
                'suggestions' => $suggestions,
                'hint' => 'No exact skill match. Try one of these, or use search_by_category for broader results.',
            ]);
        }

        $matchingMembers = $members->filter(fn ($m) => $m->skills->contains('id', $skill->id));

        if ($matchingMembers->isEmpty()) {
            return json_encode([
                'skill' => $skill->name,
                'people' => [],
                'total' => 0,
                'coverage_assessment' => 'No coverage - nobody in the team has this skill',
            ]);
        }

        $people = $matchingMembers->map(function ($member) use ($skill) {
            $level = $member->skills->find($skill->id)->pivot->level;
            $entry = $this->formatPersonWithContactability($member, [
                'level' => $level->label(),
            ]);
            if ($level === SkillLevel::Low) {
                $entry['note'] = 'Currently learning';
            }

            return $entry;
        })->sortByDesc(fn ($p) => SkillLevel::tryFrom($p['level'])?->value ?? 0)->values()->toArray();

        $highCount = collect($people)->filter(fn ($p) => $p['level'] === 'High')->count();
        $mediumCount = collect($people)->filter(fn ($p) => $p['level'] === 'Medium')->count();
        $lowCount = collect($people)->filter(fn ($p) => $p['level'] === 'Low')->count();

        $assessment = $this->assessSkillCoverage($highCount, $mediumCount, $lowCount);

        return json_encode([
            'skill' => $skill->name,
            'people' => $people,
            'total' => count($people),
            'coverage_assessment' => $assessment,
        ], JSON_PRETTY_PRINT);
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

        $skills = Skill::approved()
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

        if (empty($skills)) {
            $skills = Skill::approved()
                ->inRandomOrder()
                ->limit(6)
                ->get()
                ->map(fn ($s) => [
                    'name' => $s->name,
                    'category' => $s->category?->name,
                ])
                ->toArray();
        }

        return $skills;
    }

    protected function assessCoverage(SkillLevel $theirLevel, SkillLevel $personLevel): bool|string
    {
        if ($theirLevel->value >= $personLevel->value) {
            return true;
        }
        if ($theirLevel === SkillLevel::Medium && $personLevel === SkillLevel::High) {
            return 'partially';
        }
        if ($theirLevel === SkillLevel::Low) {
            return 'learning only';
        }

        return 'partially';
    }

    protected function getFirstName($person): string
    {
        return explode(' ', $person->forenames)[0];
    }

    protected function assessSkillCoverage(int $high, int $medium, int $low): string
    {
        if ($high >= 2) {
            return 'Good coverage - multiple experts available';
        }
        if ($high === 1 && $medium >= 1) {
            return 'Reasonable coverage - 1 expert plus backup';
        }
        if ($high === 1) {
            return 'Thin coverage - only 1 expert, consider cross-training';
        }
        if ($medium >= 2) {
            return 'Moderate coverage - no experts but multiple proficient people';
        }
        if ($medium === 1) {
            return 'Limited coverage - only 1 proficient person';
        }
        if ($low > 0) {
            return "Minimal coverage - {$low} learning, no experts yet";
        }

        return 'No coverage';
    }

    protected function getContext(): CoachContext
    {
        return $this->context;
    }

    protected function generateRecommendation(array $coverage, string $personName): string
    {
        $noBackup = collect($coverage)->filter(fn ($c) => isset($c['risk']))->pluck('skill')->toArray();

        if (empty($noBackup)) {
            return "Good news - all of {$personName}'s skills have some backup coverage.";
        }

        if (count($noBackup) === 1) {
            return "{$noBackup[0]} has no backup. Consider cross-training someone.";
        }

        $skills = implode(' and ', array_slice($noBackup, 0, 2));
        $more = count($noBackup) > 2 ? ' (and '.(count($noBackup) - 2).' more)' : '';

        return "{$skills}{$more} have no backup. Worth discussing cross-training priorities.";
    }
}
