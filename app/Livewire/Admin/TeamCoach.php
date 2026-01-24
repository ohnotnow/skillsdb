<?php

namespace App\Livewire\Admin;

use App\Enums\CoachMode;
use App\Models\CoachConversation;
use App\Models\Team;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\CoachService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TeamCoach extends Component
{
    public string $prompt = '';

    public array $messages = [];

    public ?int $conversationId = null;

    public ?int $teamId = null;

    public function mount(): void
    {
        $user = auth()->user();
        $team = $user->managedTeams()->first();

        if ($team) {
            $this->teamId = $team->id;
            $this->loadConversation();
        }
    }

    public function send(CoachService $coach, CoachContext $context): void
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
        $context->setMode(CoachMode::Team);
        $context->setTeam($team);

        $conversation = $this->conversationId
            ? CoachConversation::find($this->conversationId)
            : $this->getOrCreateTeamConversation($user, $team);

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
        $team = Team::find($this->teamId);

        if (! $team) {
            return;
        }

        $conversation = $user->coachConversations()->create([
            'mode' => CoachMode::Team,
            'team_id' => $team->id,
        ]);

        $this->conversationId = $conversation->id;
        $this->reset('messages');
    }

    protected function loadConversation(): void
    {
        $user = auth()->user();

        $conversation = $user->coachConversations()
            ->where('mode', CoachMode::Team)
            ->where('team_id', $this->teamId)
            ->first();

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

    protected function getOrCreateTeamConversation($user, Team $team): CoachConversation
    {
        return $user->coachConversations()
            ->where('mode', CoachMode::Team)
            ->where('team_id', $team->id)
            ->firstOrCreate([
                'mode' => CoachMode::Team,
                'team_id' => $team->id,
            ]);
    }

    public function render()
    {
        return view('livewire.admin.team-coach', [
            'team' => Team::find($this->teamId),
        ]);
    }
}
