<?php

namespace App\Livewire;

use App\Enums\CoachMode;
use App\Livewire\Concerns\HasCoachConversations;
use App\Models\CoachConversation;
use App\Services\SkillsCoach\CoachService;
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

    protected array $exportEagerLoads = ['messages'];

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

        $this->dispatch('message-sent');

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

        $this->dispatch('message-received');
    }

    public function clearChat(): void
    {
        $user = auth()->user();
        $conversation = $user->coachConversations()->create([
            'mode' => CoachMode::Personal,
        ]);
        $this->conversationId = $conversation->id;
        $this->reset('messages');
    }

    protected function loadConversation(): void
    {
        $user = auth()->user();

        $conversation = $this->conversationId
            ? $user->coachConversations()->find($this->conversationId)
            : $user->coachConversations()->personal()->first();

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
        } else {
            $this->reset('messages');
        }
    }

    public function render()
    {
        return view('livewire.skills-coach');
    }
}
