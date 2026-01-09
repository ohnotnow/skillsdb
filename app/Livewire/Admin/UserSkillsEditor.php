<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class UserSkillsEditor extends Component
{
    public User $user;

    public function render()
    {
        return view('livewire.admin.user-skills-editor');
    }
}
