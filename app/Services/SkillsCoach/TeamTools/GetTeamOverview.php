<?php

namespace App\Services\SkillsCoach\TeamTools;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\SkillHistory;
use App\Services\SkillsCoach\CoachContext;
use Prism\Prism\Tool;

class GetTeamOverview extends Tool
{
    public function __construct(
        protected CoachContext $context
    ) {
        $this
            ->as('get_team_overview')
            ->for('Get a high-level picture of the actual humans in the team: who they are, what they know, what they are learning')
            ->using($this);
    }

    public function __invoke(): string
    {
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        $team->load('members.skills.category', 'members.teams', 'members.skillHistory', 'manager');

        $members = $team->members;
        $people = $members->map(fn ($member) => $this->buildPersonData($member))->values()->toArray();

        $allSkillIds = $members->flatMap(fn ($m) => $m->skills->pluck('id'))->unique();
        $recentActivity = $this->getRecentActivitySummary($members);

        return json_encode([
            'manager' => $team->manager->full_name,
            'people' => $people,
            'total_people' => $members->count(),
            'total_unique_skills' => $allSkillIds->count(),
            'recent_activity' => $recentActivity,
        ], JSON_PRETTY_PRINT);
    }

    protected function buildPersonData($member): array
    {
        $topSkills = $member->skills
            ->filter(fn ($s) => $s->pivot->level->value >= SkillLevel::Medium->value)
            ->sortByDesc(fn ($s) => $s->pivot->level->value)
            ->take(4)
            ->map(fn ($s) => $s->name.' ('.$s->pivot->level->label().')')
            ->values()
            ->toArray();

        $sixtyDaysAgo = now()->subDays(60);
        $recentlyLearning = $member->skillHistory
            ->where('event_type', SkillHistoryEvent::Added)
            ->where('new_level', SkillLevel::Low->value)
            ->where('created_at', '>=', $sixtyDaysAgo)
            ->map(fn ($h) => $h->skill->name ?? 'Unknown')
            ->unique()
            ->values()
            ->toArray();

        $lastActivity = $member->skillHistory->first()?->created_at;

        return [
            'name' => $member->full_name,
            'org_teams' => $member->teams->pluck('name')->toArray(),
            'skill_count' => $member->skills->count(),
            'top_skills' => $topSkills,
            'recently_learning' => $recentlyLearning,
            'last_active' => $lastActivity ? $lastActivity->diffForHumans() : 'never',
        ];
    }

    protected function getRecentActivitySummary($members): string
    {
        $memberIds = $members->pluck('id');
        $thirtyDaysAgo = now()->subDays(30);

        $recentHistory = SkillHistory::whereIn('user_id', $memberIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get();

        $added = $recentHistory->where('event_type', SkillHistoryEvent::Added)->count();
        $levelledUp = $recentHistory->where('event_type', SkillHistoryEvent::LevelledUp)->count();

        $parts = [];
        if ($added > 0) {
            $parts[] = "{$added} skill".($added === 1 ? '' : 's').' added';
        }
        if ($levelledUp > 0) {
            $parts[] = "{$levelledUp} level-up".($levelledUp === 1 ? '' : 's');
        }

        return empty($parts)
            ? 'No activity in the last 30 days'
            : implode(', ', $parts).' in the last 30 days';
    }
}
