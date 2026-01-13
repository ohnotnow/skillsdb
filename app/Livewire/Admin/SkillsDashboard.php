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

    #[Url(except: '')]
    public string $teamSearch = '';

    #[Url(except: '')]
    public string $skillFilter = '';

    // Summary stats

    #[Computed]
    public function teamMemberCount(): int
    {
        return User::count();
    }

    #[Computed]
    public function skillLevelCounts(): array
    {
        $counts = \App\Models\SkillUser::query()
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        return [
            'high' => $counts[SkillLevel::High->value] ?? 0,
            'medium' => $counts[SkillLevel::Medium->value] ?? 0,
            'low' => $counts[SkillLevel::Low->value] ?? 0,
        ];
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
        $totalTeamMembers = $this->teamMemberCount;

        $categories = SkillCategory::with(['skills' => function ($query) {
            $query->approved()->with('users');
        }])->get();

        return $categories->map(function ($category) use ($totalTeamMembers) {
            $skills = $category->skills;

            // Get unique users who have any skill in this category
            $userIds = $skills->flatMap(fn ($s) => $s->users->pluck('id'))->unique();
            $userCount = $userIds->count();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'skillCount' => $skills->count(),
                'userCount' => $userCount,
                'percentage' => $totalTeamMembers > 0 ? round(($userCount / $totalTeamMembers) * 100) : 0,
            ];
        })->sortByDesc('userCount')->values()->toArray();
    }

    #[Computed]
    public function categoryColours(): array
    {
        $palette = [
            'sky', 'emerald', 'violet', 'amber', 'rose',
            'cyan', 'lime', 'fuchsia', 'orange', 'indigo',
        ];

        $categories = SkillCategory::orderBy('name')->pluck('id')->values();

        $colours = [];
        foreach ($categories as $index => $categoryId) {
            $colours[$categoryId] = $palette[$index % count($palette)];
        }

        return $colours;
    }

    public function getCategoryColour(?int $categoryId): string
    {
        if (! $categoryId) {
            return 'zinc';
        }

        return $this->categoryColours[$categoryId] ?? 'zinc';
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

    // By Category tab - detailed breakdown

    #[Computed]
    public function categoriesWithSkills(): array
    {
        return SkillCategory::with(['skills' => function ($query) {
            $query->approved()
                ->with(['users' => fn ($q) => $q->withPivot('level')])
                ->withCount('users')
                ->orderBy('name');
        }])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $skills = $category->skills->map(function ($skill) {
                    $levelCounts = $skill->users->groupBy(fn ($u) => $u->pivot->level)
                        ->map->count();

                    return [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'userCount' => $skill->users_count,
                        'high' => $levelCounts[SkillLevel::High->value] ?? 0,
                        'medium' => $levelCounts[SkillLevel::Medium->value] ?? 0,
                        'low' => $levelCounts[SkillLevel::Low->value] ?? 0,
                    ];
                });

                $userIds = $category->skills->flatMap(fn ($s) => $s->users->pluck('id'))->unique();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'skillCount' => $skills->count(),
                    'userCount' => $userIds->count(),
                    'skills' => $skills->toArray(),
                ];
            })
            ->toArray();
    }

    // Team tab - user directory

    #[Computed]
    public function teamMembers(): array
    {
        return User::query()
            ->withCount('skills')
            ->with(['skills' => fn ($q) => $q->withPivot('level')])
            ->when($this->teamSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('forenames', 'like', "%{$this->teamSearch}%")
                        ->orWhere('surname', 'like', "%{$this->teamSearch}%");
                });
            })
            ->when($this->skillFilter, function ($query) {
                $query->whereHas('skills', fn ($q) => $q->where('skill_id', $this->skillFilter));
            })
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get()
            ->map(function ($user) {
                $distribution = $user->getSkillDistribution();

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'skillCount' => $user->skills_count,
                    'high' => $distribution['high'],
                    'medium' => $distribution['medium'],
                    'low' => $distribution['low'],
                    'lastUpdated' => $user->getLastUpdatedText(),
                    'isStale' => $user->hasStaleSkills(),
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function allSkillsForFilter()
    {
        return Skill::approved()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.skills-dashboard');
    }
}
