<?php

namespace App\Ai\Tools\TeamTools;

use App\Enums\SkillHistoryEvent;
use App\Models\SkillHistory;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetRecentActivity implements Tool
{
    use HandlesContactability;

    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'What has been happening with the team? Who is growing, who is stagnating, what skills are trending. Answers: "What has the team been up to?" or "Are people using the system?"';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(365),
            'person_name' => $schema->string(),
        ];
    }

    public function handle(Request $request): string
    {
        $days = $request['days'] ?? 30;
        $person_name = $request['person_name'] ?? null;
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        $team->load('members');
        $members = $team->members;
        $memberIds = $members->pluck('id');

        if ($person_name) {
            return $this->getActivityForPerson($person_name, $members, $days);
        }

        $cutoffDate = now()->subDays($days);

        $history = SkillHistory::whereIn('user_id', $memberIds)
            ->where('created_at', '>=', $cutoffDate)
            ->with('skill', 'user')
            ->get();

        $summary = $this->buildSummary($history, $members);
        $activityByPerson = $this->buildActivityByPerson($history, $members);
        $trendingSkills = $this->buildTrendingSkills($history);
        $decliningEngagement = $this->findDecliningEngagement($members, $cutoffDate);
        $insight = $this->generateInsight($summary, $trendingSkills, $decliningEngagement);

        return json_encode([
            'period' => "Last {$days} days",
            'summary' => $summary,
            'activity_by_person' => $activityByPerson,
            'trending_skills' => $trendingSkills,
            'declining_engagement' => $decliningEngagement,
            'insight' => $insight,
        ], JSON_PRETTY_PRINT);
    }

    protected function getActivityForPerson(string $personName, $members, int $days): string
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

        $cutoffDate = now()->subDays($days);

        $history = SkillHistory::where('user_id', $person->id)
            ->where('created_at', '>=', $cutoffDate)
            ->with('skill')
            ->orderByDesc('created_at')
            ->get();

        $events = $history->map(function ($h) {
            return [
                'event' => $this->formatEvent($h),
                'date' => $h->created_at->format('Y-m-d'),
                'days_ago' => $h->created_at->diffInDays(now()),
            ];
        })->toArray();

        $added = $history->where('event_type', SkillHistoryEvent::Added)->count();
        $levelledUp = $history->where('event_type', SkillHistoryEvent::LevelledUp)->count();
        $removed = $history->where('event_type', SkillHistoryEvent::Removed)->count();

        return json_encode([
            'person' => $person->full_name,
            'period' => "Last {$days} days",
            'events' => $events,
            'summary' => [
                'skills_added' => $added,
                'level_ups' => $levelledUp,
                'skills_removed' => $removed,
                'total_events' => $history->count(),
            ],
            'last_activity' => $history->first()?->created_at->diffForHumans() ?? 'No activity in this period',
        ], JSON_PRETTY_PRINT);
    }

    protected function buildSummary($history, $members): array
    {
        $activeUserIds = $history->pluck('user_id')->unique();

        return [
            'skills_added' => $history->where('event_type', SkillHistoryEvent::Added)->count(),
            'level_ups' => $history->where('event_type', SkillHistoryEvent::LevelledUp)->count(),
            'skills_removed' => $history->where('event_type', SkillHistoryEvent::Removed)->count(),
            'active_people' => $activeUserIds->count(),
            'inactive_people' => $members->count() - $activeUserIds->count(),
        ];
    }

    protected function buildActivityByPerson($history, $members): array
    {
        $historyByUser = $history->groupBy('user_id');

        return $members->map(function ($member) use ($historyByUser) {
            $userHistory = $historyByUser->get($member->id, collect());

            if ($userHistory->isEmpty()) {
                $lastActivity = SkillHistory::where('user_id', $member->id)
                    ->orderByDesc('created_at')
                    ->first();

                return $this->formatPersonWithContactability($member, [
                    'events' => 0,
                    'last_activity' => $lastActivity?->created_at->diffForHumans() ?? 'never',
                    'note' => 'Has not updated recently',
                ]);
            }

            $highlights = $userHistory->take(3)->map(fn ($h) => $this->formatEvent($h))->toArray();

            return $this->formatPersonWithContactability($member, [
                'events' => $userHistory->count(),
                'highlights' => $highlights,
            ]);
        })
            ->sortByDesc('events')
            ->values()
            ->toArray();
    }

    protected function buildTrendingSkills($history): array
    {
        $addedAndLevelledUp = $history->filter(fn ($h) => in_array($h->event_type, [SkillHistoryEvent::Added, SkillHistoryEvent::LevelledUp]));

        return $addedAndLevelledUp
            ->groupBy(fn ($h) => $h->skill->name ?? 'Unknown')
            ->map(function ($events, $skillName) {
                $addedCount = $events->where('event_type', SkillHistoryEvent::Added)->count();
                $levelUpCount = $events->where('event_type', SkillHistoryEvent::LevelledUp)->count();

                $type = match (true) {
                    $addedCount > 0 && $levelUpCount > 0 => 'Growing interest and deepening',
                    $addedCount > 0 => 'Growing interest',
                    $levelUpCount > 0 => 'Level-ups',
                    default => 'Activity',
                };

                return [
                    'skill' => $skillName,
                    'events' => $events->count(),
                    'type' => $type,
                ];
            })
            ->sortByDesc('events')
            ->values()
            ->take(5)
            ->toArray();
    }

    protected function findDecliningEngagement($members, $cutoffDate): array
    {
        return $members
            ->filter(function ($member) use ($cutoffDate) {
                $hasOlderActivity = SkillHistory::where('user_id', $member->id)
                    ->where('created_at', '<', $cutoffDate)
                    ->exists();

                $hasRecentActivity = SkillHistory::where('user_id', $member->id)
                    ->where('created_at', '>=', $cutoffDate)
                    ->exists();

                return $hasOlderActivity && ! $hasRecentActivity;
            })
            ->map(function ($member) {
                $lastActivity = SkillHistory::where('user_id', $member->id)
                    ->orderByDesc('created_at')
                    ->first();

                return $this->formatPersonWithContactability($member, [
                    'last_activity' => $lastActivity?->created_at->diffForHumans() ?? 'unknown',
                ]);
            })
            ->values()
            ->toArray();
    }

    protected function getContext(): CoachContext
    {
        return $this->context;
    }

    protected function generateInsight(array $summary, array $trending, array $declining): string
    {
        $parts = [];

        if ($summary['skills_added'] > 0 || $summary['level_ups'] > 0) {
            $parts[] = 'Good activity overall';
        } else {
            $parts[] = 'Quiet period';
        }

        if (! empty($trending)) {
            $topSkill = $trending[0]['skill'];
            $parts[] = "{$topSkill} is trending";
        }

        if (! empty($declining)) {
            $names = collect($declining)->take(2)->pluck('name')->implode(' and ');
            $parts[] = "{$names} might need a nudge - worth checking in?";
        }

        return implode('. ', $parts).'.';
    }

    protected function formatEvent($history): string
    {
        $skillName = $history->skill->name ?? 'Unknown';

        return match ($history->event_type) {
            SkillHistoryEvent::Added => "Added {$skillName}",
            SkillHistoryEvent::Removed => "Removed {$skillName}",
            SkillHistoryEvent::LevelledUp => "{$skillName}: levelled up",
            SkillHistoryEvent::LevelledDown => "{$skillName}: levelled down",
            default => "{$history->event_type->label()} {$skillName}",
        };
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
