<?php

namespace App\Livewire;

use App\Enums\CoachMode;
use Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConversationHistory extends Component
{
    public CoachMode $mode;

    public ?int $teamId = null;

    public ?int $currentConversationId = null;

    public string $search = '';

    public function mount(CoachMode $mode, ?int $teamId = null, ?int $currentConversationId = null): void
    {
        $this->mode = $mode;
        $this->teamId = $teamId;
        $this->currentConversationId = $currentConversationId;
    }

    #[Computed]
    public function conversations(): Collection
    {
        $query = auth()->user()->coachConversations()
            ->where('mode', $this->mode)
            ->with(['messages' => fn ($q) => $q->oldest()->limit(1)])
            ->latest();

        if ($this->mode === CoachMode::Team && $this->teamId) {
            $query->where('team_id', $this->teamId);
        }

        if ($this->search) {
            $query->whereHas('messages', fn ($q) => $q->where('content', 'like', "%{$this->search}%"));
        }

        return $query->get();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->dispatch('conversation-selected', conversationId: $conversationId);
        Flux::modal('conversation-history')->close();
    }

    public function render()
    {
        return view('livewire.conversation-history');
    }
}
