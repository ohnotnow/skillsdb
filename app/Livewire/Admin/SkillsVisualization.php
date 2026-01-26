<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsVisualization extends Component
{
    public function render()
    {
        return view('livewire.admin.skills-visualization');
    }
}
