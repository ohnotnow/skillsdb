<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class UserSkillsManager extends Component
{
    #[Url]
    public $search = '';

    #[Computed]
    public function users()
    {
        return User::query()
            ->withCount('skills')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('forenames', 'like', "%{$this->search}%")
                        ->orWhere('surname', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.user-skills-manager');
    }
}
