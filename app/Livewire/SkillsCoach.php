<?php

namespace App\Livewire;

use App\Models\CoachConversation;
use App\Services\SkillsCoach\CoachService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsCoach extends Component
{
    public string $prompt = '';

    public array $messages = [];

    public ?int $conversationId = null;

    public function mount(): void
    {
        $this->loadConversation();
    }

    public function send(CoachService $coach): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ]);

        $user = auth()->user();
        $conversation = $this->conversationId
            ? CoachConversation::find($this->conversationId)
            : null;

        // Add user message to UI immediately
        $this->messages[] = [
            'role' => 'user',
            'content' => $this->prompt,
        ];

        $prompt = $this->prompt;
        $this->reset('prompt');

        // Get response from coach (this persists both messages)
        $response = $coach->chat($user, $prompt, $conversation);

        // Update conversation ID if this was a new conversation
        $this->conversationId = $response->conversation->id;

        // Add assistant response to UI
        $this->messages[] = [
            'role' => 'assistant',
            'content' => $response->content,
        ];
    }

    public function clearChat(CoachService $coach): void
    {
        $user = auth()->user();
        $conversation = $coach->startNewConversation($user);
        $this->conversationId = $conversation->id;
        $this->reset('messages');
    }

    protected function loadConversation(): void
    {
        $user = auth()->user();
        $conversation = $user->coachConversations()->first();

        if ($conversation) {
            $this->conversationId = $conversation->id;
            $this->messages = $conversation->messages()
                ->oldest()
                ->get()
                ->map(fn ($m) => [
                    'role' => $m->role->value,
                    'content' => $m->content,
                ])
                ->toArray();
        }
    }

    public function render()
    {
        return view('livewire.skills-coach');
    }
}
