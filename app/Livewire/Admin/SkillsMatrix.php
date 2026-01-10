<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsMatrix extends Component
{
    #[Computed]
    public function users()
    {
        return User::with('skills')
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    #[Computed]
    public function skills()
    {
        return Skill::approved()
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.skills-matrix');
    }
}
