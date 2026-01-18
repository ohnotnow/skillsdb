<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class HomePage extends Component
{
    #[Url(except: 'skills')]
    public $tab = 'skills';

    public function render()
    {
        return view('livewire.home-page');
    }
}
