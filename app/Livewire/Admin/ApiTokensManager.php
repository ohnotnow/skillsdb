<?php

namespace App\Livewire\Admin;

use Carbon\Carbon;
use Flux;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ApiTokensManager extends Component
{
    public string $tokenName = '';

    public string $expiresAt = '';

    public ?string $newlyCreatedToken = null;

    public ?int $deletingTokenId = null;

    #[Computed]
    public function tokens()
    {
        return PersonalAccessToken::query()
            ->with('tokenable')
            ->orderByDesc('created_at')
            ->get();
    }

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ]);

        $expiresAt = $this->expiresAt ? Carbon::parse($this->expiresAt) : null;

        $token = Auth::user()->createToken(
            $this->tokenName,
            ['*'],
            $expiresAt
        );

        $this->newlyCreatedToken = $token->plainTextToken;
        unset($this->tokens);

        Flux::toast(heading: 'API token created.', text: 'Copy it now - it won\'t be shown again.', variant: 'success');
    }

    public function resetCreateModal(): void
    {
        $this->reset(['tokenName', 'expiresAt', 'newlyCreatedToken']);
    }

    public function confirmDelete(int $tokenId): void
    {
        $this->deletingTokenId = $tokenId;
    }

    public function cancelDelete(): void
    {
        $this->deletingTokenId = null;
    }

    public function deleteToken(): void
    {
        if (! $this->deletingTokenId) {
            return;
        }

        $token = PersonalAccessToken::findOrFail($this->deletingTokenId);
        $token->delete();

        $this->deletingTokenId = null;
        unset($this->tokens);

        Flux::toast(heading: 'API token deleted.', text: '', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.api-tokens-manager');
    }
}
