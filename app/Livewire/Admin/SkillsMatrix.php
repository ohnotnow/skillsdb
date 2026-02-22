<?php

namespace App\Livewire\Admin;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SkillHistory;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Ohffs\SimpleSpout\ExcelSheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property \Carbon\Carbon $earliestDate
 * @property int $timelineMax
 * @property \Carbon\Carbon $viewingDate
 * @property \Illuminate\Database\Eloquent\Collection $users
 * @property \Illuminate\Database\Eloquent\Collection $skills
 * @property array $categoryColours
 * @property \Illuminate\Database\Eloquent\Collection $allUsers
 * @property \Illuminate\Database\Eloquent\Collection $allSkills
 * @property array<int, array<int, \App\Enums\SkillLevel>> $skillLevelsAtDate
 */
#[Layout('components.layouts.app')]
class SkillsMatrix extends Component
{
    #[Url(except: '', history: 'replace')]
    public array $selectedSkills = [];

    #[Url(except: '', history: 'replace')]
    public array $selectedUsers = [];

    public int $timelinePosition = 0;

    public function mount(): void
    {
        $this->timelinePosition = $this->timelineMax;
    }

    #[Computed]
    public function earliestDate(): Carbon
    {
        return SkillHistory::first()?->created_at ?? now();
    }

    #[Computed]
    public function timelineMax(): int
    {
        return (int) $this->earliestDate->diffInDays(now());
    }

    #[Computed]
    public function viewingDate(): Carbon
    {
        return $this->earliestDate->copy()->addDays($this->timelinePosition);
    }

    #[Computed]
    public function users()
    {
        if (empty($this->selectedUsers) && empty($this->selectedSkills)) {
            return $this->allUsers->load('skills');
        }

        return User::with('skills')
            ->when($this->selectedUsers, fn ($q) => $q->whereIn('id', $this->selectedUsers))
            ->when($this->selectedSkills, fn ($q) => $q->whereHas('skills', fn ($q) => $q->whereIn('skill_id', $this->selectedSkills)))
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    #[Computed]
    public function skills()
    {
        return Skill::approved()
            ->with('category')
            ->when($this->selectedSkills, fn ($q) => $q->whereIn('id', $this->selectedSkills))
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->category?->name ?? 'zzz') <=> ($b->category?->name ?? 'zzz'),
                fn ($a, $b) => $a->name <=> $b->name,
            ])
            ->values();
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

    #[Computed]
    public function allUsers()
    {
        return User::orderBy('surname')->orderBy('forenames')->get();
    }

    #[Computed]
    public function allSkills()
    {
        return Skill::approved()->orderBy('name')->get();
    }

    /**
     * Pre-compute all skill levels at the viewing date in a single query.
     * Returns a nested array: [user_id => [skill_id => SkillLevel]]
     *
     * @return array<int, array<int, SkillLevel>>
     */
    #[Computed]
    public function skillLevelsAtDate(): array
    {
        $userIds = $this->users->pluck('id');
        $skillIds = $this->skills->pluck('id');

        if ($userIds->isEmpty() || $skillIds->isEmpty()) {
            return [];
        }

        $history = SkillHistory::whereIn('user_id', $userIds)
            ->whereIn('skill_id', $skillIds)
            ->where('created_at', '<=', $this->viewingDate)
            ->orderBy('created_at')
            ->get();

        $levels = [];
        $grouped = $history->groupBy(fn ($h) => "{$h->user_id}-{$h->skill_id}");

        foreach ($grouped as $key => $events) {
            $latest = $events->last();
            [$userId, $skillId] = explode('-', $key);

            if ($latest->event_type !== SkillHistoryEvent::Removed && $latest->new_level) {
                $levels[(int) $userId][(int) $skillId] = SkillLevel::from($latest->new_level);
            }
        }

        return $levels;
    }

    public function getSkillLevelAtDate(int $userId, int $skillId): ?SkillLevel
    {
        return $this->skillLevelsAtDate[$userId][$skillId] ?? null;
    }

    public function export(): StreamedResponse
    {
        $skills = $this->skills;
        $users = $this->users;

        $headers = ['Name', ...$skills->pluck('name')->toArray()];

        $rows = $users->map(function ($user) use ($skills) {
            $row = [$user->full_name];
            foreach ($skills as $skill) {
                $level = $user->getSkillLevel($skill);
                $row[] = $level ? $level->label() : '';
            }

            return $row;
        })->toArray();

        $data = [$headers, ...$rows];

        $filename = (new ExcelSheet)->generate($data);

        return response()->streamDownload(function () use ($filename) {
            echo file_get_contents($filename);
        }, 'skills-matrix-'.now()->format('Y-m-d').'.xlsx');
    }

    public function render()
    {
        return view('livewire.admin.skills-matrix');
    }
}
