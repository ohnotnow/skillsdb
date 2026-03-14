<?php

use App\Ai\Agents\PersonalCoachAgent;
use App\Ai\Agents\TeamCoachAgent;
use App\Enums\CoachMode;
use App\Livewire\ConversationHistory;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createConversationWithMessage(User $user, string $content, string $agentClass = PersonalCoachAgent::class, ?int $teamId = null): AgentConversation
{
    $conversation = AgentConversation::create([
        'id' => (string) Str::uuid7(),
        'user_id' => $user->id,
        'title' => Str::limit($content, 100),
    ]);

    AgentConversationMessage::create([
        'id' => (string) Str::uuid7(),
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'agent' => $agentClass,
        'role' => 'user',
        'content' => $content,
        'meta' => $teamId ? ['team_id' => $teamId] : [],
    ]);

    return $conversation;
}

it('displays the users personal conversations', function () {
    $user = User::factory()->create();
    createConversationWithMessage($user, 'My first message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->assertSee('My first message');
});

it('does not show other users conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    createConversationWithMessage($otherUser, 'Secret message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->assertDontSee('Secret message')
        ->assertSee('No previous conversations');
});

it('only shows team conversations in team mode', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    createConversationWithMessage($user, 'Personal message', PersonalCoachAgent::class);
    createConversationWithMessage($user, 'Team message', TeamCoachAgent::class, $team->id);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Team, 'teamId' => $team->id])
        ->assertSee('Team message')
        ->assertDontSee('Personal message');
});

it('can search conversations by message content', function () {
    $user = User::factory()->create();

    createConversationWithMessage($user, 'Tell me about Kubernetes');
    createConversationWithMessage($user, 'Help with Python');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->assertSee('Kubernetes')
        ->assertSee('Python')
        ->set('search', 'Kubernetes')
        ->assertSee('Kubernetes')
        ->assertDontSee('Python');
});

it('emits conversation-selected event when selecting a conversation', function () {
    $user = User::factory()->create();
    $conversation = createConversationWithMessage($user, 'Test message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->call('selectConversation', $conversation->id)
        ->assertDispatched('conversation-selected', conversationId: $conversation->id);
});

it('highlights the current active conversation', function () {
    $user = User::factory()->create();
    $conversation = createConversationWithMessage($user, 'Test message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, [
            'mode' => CoachMode::Personal,
            'currentConversationId' => $conversation->id,
        ])
        ->assertSeeHtml('border-accent');
});

it('shows empty state when search has no results', function () {
    $user = User::factory()->create();
    createConversationWithMessage($user, 'Test message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->set('search', 'nonexistent query')
        ->assertSee('No conversations match your search');
});

it('can delete a conversation', function () {
    $user = User::factory()->create();
    $conversation = createConversationWithMessage($user, 'Test message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->assertSee('Test message')
        ->call('deleteConversation', $conversation->id)
        ->assertDontSee('Test message');

    expect(AgentConversation::find($conversation->id))->toBeNull();
    expect(AgentConversationMessage::where('conversation_id', $conversation->id)->count())->toBe(0);
});

it('emits event when deleting the active conversation', function () {
    $user = User::factory()->create();
    $conversation = createConversationWithMessage($user, 'Test message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, [
            'mode' => CoachMode::Personal,
            'currentConversationId' => $conversation->id,
        ])
        ->call('deleteConversation', $conversation->id)
        ->assertDispatched('conversation-deleted-active');
});

it('does not emit event when deleting a non-active conversation', function () {
    $user = User::factory()->create();
    $activeConversation = createConversationWithMessage($user, 'Active message');
    $otherConversation = createConversationWithMessage($user, 'Other message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, [
            'mode' => CoachMode::Personal,
            'currentConversationId' => $activeConversation->id,
        ])
        ->call('deleteConversation', $otherConversation->id)
        ->assertNotDispatched('conversation-deleted-active');
});

it('can delete all conversations', function () {
    $user = User::factory()->create();
    createConversationWithMessage($user, 'Message one');
    createConversationWithMessage($user, 'Message two');
    createConversationWithMessage($user, 'Message three');

    expect(AgentConversation::where('user_id', $user->id)->count())->toBe(3);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->call('deleteAllConversations');

    expect(AgentConversation::where('user_id', $user->id)->count())->toBe(0);
});

it('cannot delete another users conversation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherConversation = createConversationWithMessage($otherUser, 'Other user message');

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->call('deleteConversation', $otherConversation->id);

    // Conversation should still exist - silently ignored since it's not in the user's list
    expect(AgentConversation::find($otherConversation->id))->not->toBeNull();
});
