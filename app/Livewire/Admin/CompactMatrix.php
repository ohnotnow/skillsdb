<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\SkillHistory;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CompactMatrix extends Component
{
    public int $timelinePosition = 0;

    public function mount(): void
    {
        $this->timelinePosition = $this->timelineMax;
    }

    #[Computed]
    public function earliestDate(): Carbon
    {
        return SkillHistory::orderBy('created_at')->first()?->created_at ?? now();
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
        $viewingDate = $this->viewingDate;

        // Batch fetch: get all skill history up to viewing date in one query
        $allHistory = SkillHistory::where('created_at', '<=', $viewingDate)
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id');

        return User::query()
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get()
            ->map(function ($user) use ($allHistory) {
                $userHistory = $allHistory->get($user->id, collect());

                return [
                    'id' => $user->id,
                    'initials' => $this->getInitials($user->forenames, $user->surname),
                    'fullName' => $user->full_name,
                    'skills' => $this->getUserSkillsFromHistory($userHistory),
                ];
            });
    }

    private function getUserSkillsFromHistory($userHistory): array
    {
        $result = [];

        // Group by skill_id and take the last (most recent) event for each
        $bySkill = $userHistory->groupBy('skill_id');

        foreach ($bySkill as $skillId => $events) {
            $latestEvent = $events->last();

            if ($latestEvent && $latestEvent->event_type !== \App\Enums\SkillHistoryEvent::Removed) {
                $result[$skillId] = $latestEvent->new_level;
            }
        }

        return $result;
    }

    #[Computed]
    public function skills()
    {
        return Skill::approved()
            ->with('category')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->category?->name ?? 'zzz') <=> ($b->category?->name ?? 'zzz'),
                fn ($a, $b) => $a->name <=> $b->name,
            ])
            ->values()
            ->map(fn ($skill) => [
                'id' => $skill->id,
                'abbr' => $this->getAbbreviation($skill->name),
                'fullName' => $skill->name,
                'categoryId' => $skill->skill_category_id,
                'categoryName' => $skill->category?->name,
            ]);
    }

    #[Computed]
    public function categoryColours(): array
    {
        $palette = [
            'sky', 'emerald', 'violet', 'amber', 'rose',
            'cyan', 'lime', 'fuchsia', 'orange', 'indigo',
        ];

        // Derive from skills to avoid separate query, sorted by name for consistent colors
        $categoryIds = $this->skills
            ->filter(fn ($s) => $s['categoryId'] !== null)
            ->unique('categoryId')
            ->sortBy('categoryName')
            ->pluck('categoryId')
            ->values();

        $colours = [];
        foreach ($categoryIds as $index => $categoryId) {
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

    private function getInitials(string $forenames, string $surname): string
    {
        $firstInitial = mb_substr($forenames, 0, 1);
        $lastInitial = mb_substr($surname, 0, 1);

        return strtoupper($firstInitial.$lastInitial);
    }

    private function getAbbreviation(string $name): string
    {
        // If it's an acronym or short already, use as-is
        if (strlen($name) <= 4) {
            return $name;
        }

        // If it has multiple words, take first letter of each (up to 4)
        $words = preg_split('/[\s\/\-]+/', $name);
        if (count($words) > 1) {
            $abbr = '';
            foreach (array_slice($words, 0, 4) as $word) {
                $abbr .= mb_substr($word, 0, 1);
            }

            return strtoupper($abbr);
        }

        // Single word - take first 3 chars
        return mb_substr($name, 0, 3);
    }

    public function render()
    {
        return view('livewire.admin.compact-matrix');
    }
}
