<?php

namespace App\Livewire;

use App\Models\SkillHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class PlaySpace extends Component
{
    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.play-space', [
            'history' => SkillHistory::with(['user', 'skill'])->latest('id')->get(),
            'oldChartData' => $user->getSkillsOverTime(),
            'newChartData' => $user->getSkillsOverTimeFromHistory(),
        ]);
    }
}
