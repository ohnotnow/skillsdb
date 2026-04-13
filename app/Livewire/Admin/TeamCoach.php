<?php

namespace App\Livewire\Admin;

use App\Ai\Agents\TeamCoachAgent;
use App\Enums\CoachMode;
use App\Livewire\Concerns\HasCoachConversations;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Team;
use App\Services\SkillsCoach\CoachContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TeamCoach extends Component
{
    use HasCoachConversations;

    public string $prompt = '';

    public array $messages = [];

    public ?string $conversationId = null;

    public ?int $teamId = null;

    // Required by HasCoachConversations trait
    protected string $exportPrefix = 'team-coach-chat-';

    protected string $exportTitle = 'Team Coach Conversation';

    public function mount(): void
    {
        $user = auth()->user();
        $team = $user->managedTeams()->first();

        if ($team) {
            $this->teamId = $team->id;
            $this->loadConversation();
        }
    }

    public function send(CoachContext $context): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ]);

        $user = auth()->user();
        $team = Team::find($this->teamId);

        if (! $team) {
            return;
        }

        // Set context for team mode
        $context->setUser($user);
        $context->setMode(CoachMode::Team);
        $context->setTeam($team);

        // Add user message to UI immediately
        $this->messages[] = [
            'role' => 'user',
            'content' => $this->prompt,
        ];

        $this->dispatch('message-sent');

        $prompt = $this->prompt;
        $this->reset('prompt');

        $agent = TeamCoachAgent::make();

        $response = $this->conversationId
            ? $agent->continue($this->conversationId, as: $user)->prompt($prompt)
            : $agent->forUser($user)->prompt($prompt);

        $this->conversationId = $response->conversationId;

        // Store team_id in the user message's meta for filtering
        AgentConversationMessage::where('conversation_id', $response->conversationId)
            ->where('role', 'user')
            ->latest()
            ->first()
            ?->update(['meta' => ['team_id' => $this->teamId]]);

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

    protected function getJsonConversationData(AgentConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'mode' => 'team',
            'team' => Team::find($this->teamId)?->name,
            'created_at' => $conversation->created_at->toIso8601String(),
            'messages' => $conversation->messages
                ->whereIn('role', ['user', 'assistant'])
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => $m->created_at->toIso8601String(),
                ])->values()->toArray(),
        ];
    }

    protected function getMarkdownHeader(AgentConversation $conversation): string
    {
        return "# {$this->exportTitle}\n\n"
            .'Team: '.Team::find($this->teamId)?->name."\n"
            .'Exported: '.now()->format('F j, Y g:ia')."\n"
            .'Started: '.$conversation->created_at->format('F j, Y g:ia')."\n\n"
            ."---\n\n";
    }

    protected function loadConversation(): void
    {
        $user = auth()->user();

        $conversation = $this->conversationId
            ? $user->agentConversations()->find($this->conversationId)
            : AgentConversation::where('user_id', $user->id)
                ->forAgent(TeamCoachAgent::class)
                ->forTeam($this->teamId)
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
        return view('livewire.admin.team-coach', [
            'team' => Team::find($this->teamId),
        ]);
    }
}
