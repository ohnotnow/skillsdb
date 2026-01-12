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

        return User::query()
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'initials' => $this->getInitials($user->forenames, $user->surname),
                'fullName' => $user->full_name,
                'skills' => $this->getUserSkillsAt($user, $viewingDate),
            ]);
    }

    private function getUserSkillsAt(User $user, Carbon $date): array
    {
        $skills = Skill::approved()->pluck('id');
        $result = [];

        foreach ($skills as $skillId) {
            $level = $this->getSkillLevelAt($user->id, $skillId, $date);
            if ($level !== null) {
                $result[$skillId] = $level;
            }
        }

        return $result;
    }

    private function getSkillLevelAt(int $userId, int $skillId, Carbon $date): ?int
    {
        $latestEvent = SkillHistory::where('user_id', $userId)
            ->where('skill_id', $skillId)
            ->where('created_at', '<=', $date)
            ->orderByDesc('created_at')
            ->first();

        if (! $latestEvent || $latestEvent->event_type === \App\Enums\SkillHistoryEvent::Removed) {
            return null;
        }

        return $latestEvent->new_level;
    }

    #[Computed]
    public function skills()
    {
        return Skill::approved()
            ->orderBy('name')
            ->get()
            ->map(fn ($skill) => [
                'id' => $skill->id,
                'abbr' => $this->getAbbreviation($skill->name),
                'fullName' => $skill->name,
            ]);
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
