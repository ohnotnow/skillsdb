<?php

namespace App\Livewire;

use App\Ai\Agents\PersonalCoachAgent;
use App\Ai\Agents\TeamCoachAgent;
use App\Enums\CoachMode;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** @property Collection $conversations */
class ConversationHistory extends Component
{
    public CoachMode $mode;

    public ?int $teamId = null;

    public ?string $currentConversationId = null;

    public string $search = '';

    public function mount(CoachMode $mode, ?int $teamId = null, ?string $currentConversationId = null): void
    {
        $this->mode = $mode;
        $this->teamId = $teamId;
        $this->currentConversationId = $currentConversationId;
    }

    #[Computed]
    public function conversations(): Collection
    {
        $agentClass = $this->mode === CoachMode::Team
            ? TeamCoachAgent::class
            : PersonalCoachAgent::class;

        $query = AgentConversation::where('user_id', auth()->id())
            ->forAgent($agentClass)
            ->with(['messages' => fn ($q) => $q->where('role', 'user')->oldest()->limit(1)])
            ->latest();

        if ($this->mode === CoachMode::Team && $this->teamId) {
            $query->forTeam($this->teamId);
        }

        if ($this->search) {
            $query->whereHas('messages', fn ($q) => $q->where('content', 'like', "%{$this->search}%"));
        }

        return $query->get();
    }

    public function selectConversation(string $conversationId): void
    {
        $this->dispatch('conversation-selected', conversationId: $conversationId);
        Flux::modal('conversation-history')->close();
    }

    public function deleteConversation(string $conversationId): void
    {
        if (! $this->conversations->contains('id', $conversationId)) {
            return;
        }

        AgentConversationMessage::where('conversation_id', $conversationId)->delete();
        AgentConversation::where('id', $conversationId)
            ->where('user_id', auth()->id())
            ->delete();

        unset($this->conversations);

        if ($this->currentConversationId === $conversationId) {
            $this->dispatch('conversation-deleted-active');
        }
    }

    public function deleteAllConversations(): void
    {
        $ids = $this->conversations->pluck('id');
        $hadActive = $ids->contains($this->currentConversationId);

        AgentConversationMessage::whereIn('conversation_id', $ids)->delete();
        AgentConversation::whereIn('id', $ids)
            ->where('user_id', auth()->id())
            ->delete();

        unset($this->conversations);

        if ($hadActive) {
            $this->dispatch('conversation-deleted-active');
        }
    }

    public function render()
    {
        return view('livewire.conversation-history');
    }
}
