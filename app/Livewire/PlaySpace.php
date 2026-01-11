<?php

namespace App\Livewire;

use App\Models\SkillHistory;
use Illuminate\View\View;
use Livewire\Component;

class PlaySpace extends Component
{
    public function render(): View
    {
        return view('livewire.play-space', [
            'history' => SkillHistory::with(['user', 'skill'])->latest('id')->get(),
        ]);
    }
}
