<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsMatrix extends Component
{
    #[Url(except: '')]
    public array $selectedSkills = [];

    #[Url(except: '')]
    public array $selectedUsers = [];

    #[Computed]
    public function users()
    {
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
            ->when($this->selectedSkills, fn ($q) => $q->whereIn('id', $this->selectedSkills))
            ->orderBy('name')
            ->get();
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

    public function render()
    {
        return view('livewire.admin.skills-matrix');
    }
}
