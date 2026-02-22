<?php

namespace App\Livewire;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property \App\Models\User $user
 * @property array $skillDistribution
 * @property string $lastUpdatedText
 * @property bool $hasStaleSkills
 * @property \Illuminate\Support\Collection $trendingSkills
 * @property array $skillsOverTime
 * @property \Illuminate\Support\Collection $colleagueInsights
 */
class SkillsDashboard extends Component
{
    public ?int $userId = null;

    public bool $teamMode = false;

    public function mount(?int $userId = null, bool $teamMode = false): void
    {
        $this->userId = $userId ?? Auth::id();
        $this->teamMode = $teamMode;
    }

    #[Computed]
    public function user(): User
    {
        return User::findOrFail($this->userId);
    }

    #[Computed]
    public function skillDistribution(): array
    {
        return $this->user->getSkillDistribution();
    }

    #[Computed]
    public function lastUpdatedText(): string
    {
        return $this->user->getLastUpdatedText();
    }

    #[Computed]
    public function hasStaleSkills(): bool
    {
        return $this->user->hasStaleSkills();
    }

    #[Computed]
    public function trendingSkills()
    {
        return Skill::getTrendingSkills();
    }

    /**
     * @return array<int, array{month: string, points: int, events: array<string>, eventText: string}>
     */
    #[Computed]
    public function skillsOverTime(): array
    {
        return $this->user->getSkillsOverTimeFromHistory();
    }

    /**
     * Get 3 random skills the user has, with colleagues who share them.
     *
     * @return \Illuminate\Support\Collection<int, array{skill: Skill, colleagues: \Illuminate\Support\Collection<int, User>}>
     */
    #[Computed]
    public function colleagueInsights()
    {
        $userId = $this->userId;

        return $this->user->skills()
            ->approved()
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->map(function ($skill) use ($userId) {
                $colleagues = $skill->users()
                    ->where('user_id', '!=', $userId)
                    ->orderBy('surname')
                    ->get();

                return [
                    'skill' => $skill,
                    'colleagues' => $colleagues,
                ];
            })
            ->filter(fn ($insight) => $insight['colleagues']->isNotEmpty());
    }

    public function render()
    {
        return view('livewire.skills-dashboard');
    }
}
