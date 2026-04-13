<?php

use App\Ai\Agents\TeamCoachAgent;
use App\Livewire\Admin\TeamCoach;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createTeamCoachConversation(User $user, Team $team, array $messages = []): AgentConversation
{
    $conversation = AgentConversation::create([
        'id' => (string) Str::uuid7(),
        'user_id' => $user->id,
        'title' => 'Team coach conversation',
    ]);

    foreach ($messages as $message) {
        AgentConversationMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'agent' => TeamCoachAgent::class,
            'role' => $message['role'],
            'content' => $message['content'],
            'meta' => $message['role'] === 'user' ? ['team_id' => $team->id] : [],
        ]);
    }

    return $conversation;
}

it('stores team_id metadata correctly so conversations appear in history', function () {
    $user = User::factory()->admin()->create();
    $team = Team::factory()->create(['manager_id' => $user->id]);

    TeamCoachAgent::fake([
        'Your team has 5 members.',
    ]);

    // Send a message to create a conversation
    Livewire::actingAs($user)
        ->test(TeamCoach::class)
        ->set('prompt', 'How many people on my team?')
        ->call('send')
        ->assertSee('How many people on my team?')
        ->assertSee('Your team has 5 members.');

    // Verify the meta is stored as a proper array (not double-encoded JSON string)
    $userMessage = AgentConversationMessage::where('role', 'user')
        ->where('content', 'How many people on my team?')
        ->first();

    expect($userMessage->meta)->toBeArray();
    expect($userMessage->meta['team_id'])->toBe($team->id);

    // Verify the conversation is findable via the forTeam scope
    $conversation = AgentConversation::where('user_id', $user->id)
        ->forAgent(TeamCoachAgent::class)
        ->forTeam($team->id)
        ->first();

    expect($conversation)->not->toBeNull();

    // Mount a fresh component — it should load the conversation from history
    Livewire::actingAs($user)
        ->test(TeamCoach::class)
        ->assertSee('How many people on my team?')
        ->assertSee('Your team has 5 members.');
});
