<?php

namespace App\Livewire\Admin;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SkillHistory;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsDashboard extends Component
{
    #[Url(except: 'overview')]
    public string $tab = 'overview';

    // Summary stats

    #[Computed]
    public function teamMemberCount(): int
    {
        return User::count();
    }

    #[Computed]
    public function skillsUsedCount(): int
    {
        return Skill::approved()
            ->whereHas('users')
            ->count();
    }

    #[Computed]
    public function totalApprovedSkills(): int
    {
        return Skill::approved()->count();
    }

    #[Computed]
    public function averageSkillsPerUser(): float
    {
        $totalSkillAssignments = User::withCount('skills')->get()->sum('skills_count');
        $userCount = $this->teamMemberCount;

        return $userCount > 0 ? round($totalSkillAssignments / $userCount, 1) : 0;
    }

    #[Computed]
    public function changesLast30Days(): int
    {
        return SkillHistory::where('created_at', '>=', now()->subDays(30))->count();
    }

    // Category strength data

    #[Computed]
    public function categoryStrength(): array
    {
        $categories = SkillCategory::with(['skills' => function ($query) {
            $query->approved()->withCount('users');
        }])->get();

        return $categories->map(function ($category) {
            $skills = $category->skills;
            $totalSkills = $skills->count();
            $usedSkills = $skills->filter(fn ($s) => $s->users_count > 0)->count();

            // Get unique users who have any skill in this category
            $userIds = $skills->flatMap(fn ($s) => $s->users->pluck('id'))->unique();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'totalSkills' => $totalSkills,
                'usedSkills' => $usedSkills,
                'userCount' => $userIds->count(),
                'percentage' => $totalSkills > 0 ? round(($usedSkills / $totalSkills) * 100) : 0,
            ];
        })->sortByDesc('userCount')->values()->toArray();
    }

    // Needs attention - skills with coverage issues

    #[Computed]
    public function needsAttention(): array
    {
        $skills = Skill::approved()
            ->with(['users' => function ($query) {
                $query->withPivot('level');
            }])
            ->get();

        $issues = [];

        foreach ($skills as $skill) {
            $userCount = $skill->users->count();
            $expertCount = $skill->users->filter(
                fn ($u) => $u->pivot->level === SkillLevel::High->value
            )->count();

            if ($userCount === 0) {
                $issues[] = [
                    'skill' => $skill->name,
                    'issue' => 'No one has this skill',
                    'severity' => 'high',
                ];
            } elseif ($userCount <= 2) {
                $issues[] = [
                    'skill' => $skill->name,
                    'issue' => "Only {$userCount} ".($userCount === 1 ? 'person' : 'people'),
                    'severity' => 'medium',
                ];
            } elseif ($expertCount === 0 && $userCount > 0) {
                $issues[] = [
                    'skill' => $skill->name,
                    'issue' => 'No experts (High level)',
                    'severity' => 'low',
                ];
            }
        }

        // Sort by severity, then limit
        $severityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($issues, fn ($a, $b) => $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']]);

        return array_slice($issues, 0, 8);
    }

    // Recent activity

    #[Computed]
    public function recentActivity(): array
    {
        return SkillHistory::with(['user', 'skill'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($h) => [
                'user' => $h->user->short_name,
                'skill' => $h->skill->name,
                'event' => $h->event_type->label(),
                'level' => $h->new_level ? SkillLevel::from($h->new_level)->label() : null,
                'colour' => $h->event_type->colour(),
                'time' => $h->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.skills-dashboard');
    }
}
