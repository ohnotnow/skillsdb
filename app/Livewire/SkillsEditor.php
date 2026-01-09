<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class SkillsEditor extends Component
{
    public ?int $userId = null;

    #[Url]
    public $search = '';

    #[Url]
    public $showMySkillsOnly = false;

    public array $userSkillLevels = [];

    // For suggesting new skills
    public bool $showSuggestModal = false;

    public string $newSkillName = '';

    public string $newSkillDescription = '';

    public $newSkillLevel = '';

    public function mount(?int $userId = null): void
    {
        $this->userId = $userId ?? Auth::id();

        // In admin context, default to showing only the user's skills
        if ($this->userId !== Auth::id()) {
            $this->showMySkillsOnly = true;
        }

        $this->loadUserSkillLevels();
    }

    #[Computed]
    public function user(): User
    {
        return User::findOrFail($this->userId);
    }

    #[Computed]
    public function isAdminContext(): bool
    {
        return $this->userId !== Auth::id();
    }

    #[Computed]
    public function skills()
    {
        $userId = $this->userId;

        return Skill::query()
            ->with('category')
            ->where(function ($query) use ($userId) {
                // Show approved skills
                $query->approved()
                    // OR pending skills created by this user (they can use their own suggestions)
                    ->orWhere(function ($q) use ($userId) {
                        $q->pending()->whereHas('users', fn ($q) => $q->where('user_id', $userId));
                    });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->showMySkillsOnly, function ($query) use ($userId) {
                $query->whereHas('users', fn ($q) => $q->where('user_id', $userId));
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function skillLevels(): array
    {
        return SkillLevel::cases();
    }

    public function updatedSearch(): void
    {
        // Reset is handled by Livewire URL binding
    }

    public function updateSkillLevel(int $skillId, string $level): void
    {
        $user = $this->user;

        if ($level === 'none' || $level === '') {
            $user->skills()->detach($skillId);
        } else {
            $user->skills()->syncWithoutDetaching([
                $skillId => ['level' => (int) $level],
            ]);
        }

        $user->touchSkillsUpdatedAt();
        $this->loadUserSkillLevels();
    }

    public function openSuggestModal(): void
    {
        $this->reset(['newSkillName', 'newSkillDescription', 'newSkillLevel']);
        $this->showSuggestModal = true;
    }

    public function closeSuggestModal(): void
    {
        $this->showSuggestModal = false;
    }

    public function suggestSkill(): void
    {
        $this->validate([
            'newSkillName' => ['required', 'string', 'max:255', 'unique:skills,name'],
            'newSkillDescription' => ['nullable', 'string', 'max:1000'],
            'newSkillLevel' => ['required', 'in:1,2,3'],
        ]);

        // Create pending skill (no approved_by or approved_at)
        $skill = Skill::create([
            'name' => $this->newSkillName,
            'description' => $this->newSkillDescription,
        ]);

        // Attach to user with the selected level
        $user = $this->user;
        $user->skills()->attach($skill->id, ['level' => (int) $this->newSkillLevel]);
        $user->touchSkillsUpdatedAt();

        $this->loadUserSkillLevels();
        $this->closeSuggestModal();

        Flux::toast(
            variant: 'success',
            heading: 'Skill suggested!',
            text: 'It has been added to your profile and will be visible to others once approved by an admin.',
        );
    }

    public function render()
    {
        return view('livewire.skills-editor');
    }

    private function loadUserSkillLevels(): void
    {
        // Get the user's actual skill levels
        $actualLevels = $this->user->skills()
            ->pluck('skill_user.level', 'skills.id')
            ->map(fn ($level) => (string) $level)
            ->toArray();

        // Pre-populate ALL approved skills with "none", then overlay actual levels
        $this->userSkillLevels = Skill::approved()
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $actualLevels[$id] ?? 'none'])
            ->toArray();
    }
}
