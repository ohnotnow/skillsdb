<?php

namespace App\Livewire;

use App\Ai\Agents\PersonalCoachAgent;
use App\Livewire\Concerns\HasCoachConversations;
use App\Models\AgentConversation;
use App\Services\SkillsCoach\CoachContext;
use Laravel\Ai\Streaming\Events\TextDelta;
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

    public ?string $pendingPrompt = null;

    public function mount(): void
    {
        $this->loadConversation();
    }

    public function send(): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ]);

        $this->messages[] = [
            'role' => 'user',
            'content' => $this->prompt,
        ];

        $this->pendingPrompt = $this->prompt;
        $this->reset('prompt');

        $this->dispatch('message-sent');

        $this->js('$wire.streamResponse()');
    }

    public function streamResponse(CoachContext $context): void
    {
        if ($this->pendingPrompt === null) {
            return;
        }

        $user = auth()->user();
        $context->setUser($user);

        $prompt = $this->pendingPrompt;
        $agent = PersonalCoachAgent::make();

        $response = $this->conversationId
            ? $agent->continue($this->conversationId, as: $user)->stream($prompt)
            : $agent->forUser($user)->stream($prompt);

        $fullText = '';
        foreach ($response as $event) {
            if ($event instanceof TextDelta) {
                $fullText .= $event->delta;
                $this->stream(to: 'coach-response', content: $event->delta);
            }
        }

        $this->conversationId = $agent->currentConversation();
        $this->pendingPrompt = null;

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $fullText,
        ];

        $this->dispatch('message-received');
    }

    public function clearChat(): void
    {
        $this->conversationId = null;
        $this->pendingPrompt = null;
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
