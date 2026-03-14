<?php

namespace App\Livewire;

use App\Ai\Agents\PersonalCoachAgent;
use App\Livewire\Concerns\HasCoachConversations;
use App\Models\AgentConversation;
use App\Services\SkillsCoach\CoachContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsCoach extends Component
{
    use HasCoachConversations;

    public string $prompt = '';

    // Required by HasCoachConversations trait
    protected string $exportPrefix = 'coach-chat-';

    protected string $exportTitle = 'Skills Coach Conversation';

    public array $messages = [];

    public ?string $conversationId = null;

    public function mount(): void
    {
        $this->loadConversation();
    }

    public function send(CoachContext $context): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ]);

        $user = auth()->user();
        $context->setUser($user);

        // Add user message to UI immediately
        $this->messages[] = [
            'role' => 'user',
            'content' => $this->prompt,
        ];

        $this->dispatch('message-sent');

        $prompt = $this->prompt;
        $this->reset('prompt');

        $agent = PersonalCoachAgent::make();

        $response = $this->conversationId
            ? $agent->continue($this->conversationId, as: $user)->prompt($prompt)
            : $agent->forUser($user)->prompt($prompt);

        $this->conversationId = $response->conversationId;

        // Add assistant response to UI
        $this->messages[] = [
            'role' => 'assistant',
            'content' => $response->text,
        ];

        $this->dispatch('message-received');
    }

    public function clearChat(): void
    {
        $this->conversationId = null;
        $this->reset('messages');
    }

    protected function loadConversation(): void
    {
        $user = auth()->user();

        $conversation = $this->conversationId
            ? $user->agentConversations()->find($this->conversationId)
            : AgentConversation::where('user_id', $user->id)
                ->forAgent(PersonalCoachAgent::class)
                ->latest()
                ->first();

        if ($conversation) {
            $this->conversationId = $conversation->id;
            $this->messages = $conversation->messages()
                ->whereIn('role', ['user', 'assistant'])
                ->oldest()
                ->get()
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                ])
                ->toArray();
        } else {
            $this->reset('messages');
        }
    }

    public function render()
    {
        return view('livewire.skills-coach');
    }
}
