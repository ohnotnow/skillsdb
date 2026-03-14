<?php

namespace App\Ai\Tools\TeamTools;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetMemberSkills implements Tool
{
    use HandlesContactability;

    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'Deep dive into a specific person - what they know, what they are learning, how they are developing. Answers: "Tell me about Sally" or "What does Jim know?"';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'person_name' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $person_name = $request['person_name'];
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        $team->load('members.skills.category', 'members.teams', 'members.skillHistory.skill');
        $members = $team->members;

        $person = $this->findMemberByName($person_name, $members);

        if (! $person) {
            $suggestions = $members->pluck('full_name')->toArray();

            return json_encode([
                'exact_match' => false,
                'searched_for' => $person_name,
                'suggestions' => $suggestions,
                'hint' => 'Could not find that person. Here are the team members.',
            ]);
        }

        $skillsByLevel = $this->groupSkillsByLevel($person);
        $recentActivity = $this->getRecentActivity($person);
        $couldMentorOn = $this->findMentoringOpportunities($person, $members);
        $couldLearnFrom = $this->findLearningOpportunities($person, $members);

        return json_encode([
            'name' => $person->full_name,
            'org_teams' => $person->teams->pluck('name')->toArray(),
            'skills' => $skillsByLevel,
            'recent_activity' => $recentActivity,
            'skill_count' => $person->skills->count(),
            'last_updated' => $person->last_updated_skills_at?->diffForHumans() ?? 'never',
            'stale' => $person->hasStaleSkills(),
            'could_mentor_on' => $couldMentorOn,
            'could_learn_from' => $couldLearnFrom,
        ], JSON_PRETTY_PRINT);
    }

    protected function groupSkillsByLevel($person): array
    {
        $skills = $person->skills;
        $history = $person->skillHistory->groupBy('skill_id');

        $grouped = [
            'expert' => [],
            'proficient' => [],
            'learning' => [],
        ];

        foreach ($skills as $skill) {
            $level = $skill->pivot->level;
            $skillHistory = $history->get($skill->id, collect());

            $levelReachedEvent = $skillHistory
                ->filter(fn ($h) => $h->new_level === $level->value)
                ->sortBy('created_at')
                ->first();

            $since = $levelReachedEvent?->created_at?->format('Y-m') ?? $skill->pivot->created_at?->format('Y-m') ?? 'unknown';

            $entry = [
                'skill' => $skill->name,
                'level' => $level->label(),
                'since' => $since,
            ];

            /** @phpstan-ignore-next-line match.unhandled */
            match ($level) {
                SkillLevel::High => $grouped['expert'][] = $entry,
                SkillLevel::Medium => $grouped['proficient'][] = $entry,
                SkillLevel::Low => $grouped['learning'][] = $entry,
            };
        }

        return $grouped;
    }

    protected function getRecentActivity($person): array
    {
        return $person->skillHistory
            ->take(10)
            ->map(function ($h) {
                $event = match ($h->event_type) {
                    SkillHistoryEvent::Added => 'Added '.$h->skill->name,
                    SkillHistoryEvent::Removed => 'Removed '.$h->skill->name,
                    SkillHistoryEvent::LevelledUp => $h->skill->name.': '.SkillLevel::from($h->old_level)->label().' → '.SkillLevel::from($h->new_level)->label(),
                    SkillHistoryEvent::LevelledDown => $h->skill->name.': '.SkillLevel::from($h->old_level)->label().' → '.SkillLevel::from($h->new_level)->label(),
                    default => $h->event_type->label().' '.$h->skill->name,
                };

                return [
                    'event' => $event,
                    'date' => $h->created_at->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    protected function findMentoringOpportunities($person, $members): array
    {
        $expertSkills = $person->skills->filter(fn ($s) => $s->pivot->level === SkillLevel::High);

        return $expertSkills
            ->filter(function ($skill) use ($members, $person) {
                return $members
                    ->filter(fn ($m) => $m->id !== $person->id)
                    ->contains(function ($m) use ($skill) {
                        $theirSkill = $m->skills->find($skill->id);

                        return ! $theirSkill || $theirSkill->pivot->level->value < SkillLevel::High->value;
                    });
            })
            ->pluck('name')
            ->toArray();
    }

    protected function findLearningOpportunities($person, $members): array
    {
        $personSkillIds = $person->skills->pluck('id')->toArray();
        $personSkillLevels = $person->skills->mapWithKeys(fn ($s) => [$s->id => $s->pivot->level]);

        $opportunities = [];

        foreach ($members as $other) {
            if ($other->id === $person->id) {
                continue;
            }

            $theirExpertSkills = $other->skills->filter(fn ($s) => $s->pivot->level === SkillLevel::High);
            $mentorEntry = $this->formatPersonWithContactability($other);

            foreach ($theirExpertSkills as $skill) {
                if (! in_array($skill->id, $personSkillIds)) {
                    $opportunities[$skill->name][$other->id] = $mentorEntry;
                } elseif ($personSkillLevels[$skill->id]->value < SkillLevel::High->value) {
                    $opportunities[$skill->name][$other->id] = $mentorEntry;
                }
            }
        }

        return collect($opportunities)
            ->map(fn ($mentors, $skill) => [
                'skill' => $skill,
                'potential_mentors' => array_values($mentors),
            ])
            ->values()
            ->take(5)
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
}
