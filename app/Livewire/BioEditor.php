<?php

namespace App\Livewire;

use Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BioEditor extends Component
{
    public bool $showModal = false;

    public string $bio = '';

    public function mount(): void
    {
        $this->bio = Auth::user()->bio ?? '';
    }

    public function openModal(): void
    {
        $this->bio = Auth::user()->fresh()->bio ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(): void
    {
        $this->validate([
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        Auth::user()->update(['bio' => $this->bio ?: null]);

        $this->showModal = false;

        Flux::toast(
            variant: 'success',
            heading: 'Bio saved',
            text: 'Your bio has been updated.',
        );
    }

    public function render()
    {
        return view('livewire.bio-editor');
    }
}
