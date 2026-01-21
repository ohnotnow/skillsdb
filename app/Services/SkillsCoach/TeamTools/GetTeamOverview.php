<?php

namespace App\Services\SkillsCoach\TeamTools;

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
            ->for('Get an overview of the team: members, skill distribution, category coverage, and recent activity')
            ->using($this);
    }

    public function __invoke(): string
    {
        $team = $this->context->getTeam();

        if (! $team) {
            return json_encode(['error' => 'No team context set']);
        }

        $team->load('members.skills.category', 'manager');

        $members = $team->members;
        $distribution = $this->calculateDistribution($members);
        $categoryCoverage = $this->calculateCategoryCoverage($members);
        $recentActivity = $this->getRecentActivity($members);

        return json_encode([
            'team_name' => $team->name,
            'manager' => $team->manager->full_name,
            'member_count' => $members->count(),
            'members' => $members->pluck('full_name')->toArray(),
            'skill_distribution' => $distribution,
            'category_coverage' => $categoryCoverage,
            'recent_activity' => $recentActivity,
        ], JSON_PRETTY_PRINT);
    }

    protected function calculateDistribution($members): array
    {
        $distribution = ['high' => 0, 'medium' => 0, 'low' => 0, 'total' => 0];

        foreach ($members as $member) {
            foreach ($member->skills as $skill) {
                $distribution['total']++;
                match ($skill->pivot->level) {
                    SkillLevel::High => $distribution['high']++,
                    SkillLevel::Medium => $distribution['medium']++,
                    SkillLevel::Low => $distribution['low']++,
                };
            }
        }

        return $distribution;
    }

    protected function calculateCategoryCoverage($members): array
    {
        $memberCount = $members->count();
        $categoryMembers = collect();

        foreach ($members as $member) {
            foreach ($member->skills as $skill) {
                $categoryName = $skill->category?->name ?? 'Uncategorised';
                if (! $categoryMembers->has($categoryName)) {
                    $categoryMembers[$categoryName] = collect();
                }
                $categoryMembers[$categoryName]->push($member->id);
            }
        }

        return $categoryMembers
            ->map(fn ($memberIds, $category) => [
                'category' => $category,
                'members_with_skills' => $memberIds->unique()->count(),
                'percentage' => $memberCount > 0
                    ? round($memberIds->unique()->count() / $memberCount * 100)
                    : 0,
            ])
            ->sortByDesc('members_with_skills')
            ->values()
            ->take(10)
            ->toArray();
    }

    protected function getRecentActivity($members): string
    {
        $memberIds = $members->pluck('id');
        $thirtyDaysAgo = now()->subDays(30);

        $recentHistory = SkillHistory::whereIn('user_id', $memberIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get();

        $added = $recentHistory->where('event_type', 'added')->count();
        $levelledUp = $recentHistory->where('event_type', 'levelled_up')->count();
        $removed = $recentHistory->where('event_type', 'removed')->count();

        $parts = [];
        if ($added > 0) {
            $parts[] = "{$added} skill".($added === 1 ? '' : 's').' added';
        }
        if ($levelledUp > 0) {
            $parts[] = "{$levelledUp} level-up".($levelledUp === 1 ? '' : 's');
        }
        if ($removed > 0) {
            $parts[] = "{$removed} removed";
        }

        return empty($parts)
            ? 'No activity in the last 30 days'
            : implode(', ', $parts).' in the last 30 days';
    }
}
