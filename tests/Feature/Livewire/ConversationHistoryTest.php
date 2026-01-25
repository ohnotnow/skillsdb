<?php

use App\Enums\CoachMessageRole;
use App\Enums\CoachMode;
use App\Livewire\ConversationHistory;
use App\Models\CoachConversation;
use App\Models\CoachMessage;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('displays the users personal conversations', function () {
    $user = User::factory()->create();
    $conversation = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'My first message',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->assertSee('My first message');
});

it('does not show other users conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = CoachConversation::factory()->create(['user_id' => $otherUser->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Secret message',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->assertDontSee('Secret message')
        ->assertSee('No previous conversations');
});

it('only shows team conversations in team mode', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $personalConversation = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $personalConversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Personal message',
    ]);

    $teamConversation = CoachConversation::factory()->teamMode($team)->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $teamConversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Team message',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Team, 'teamId' => $team->id])
        ->assertSee('Team message')
        ->assertDontSee('Personal message');
});

it('can search conversations by message content', function () {
    $user = User::factory()->create();

    $conv1 = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conv1->id,
        'role' => CoachMessageRole::User,
        'content' => 'Tell me about Kubernetes',
    ]);

    $conv2 = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conv2->id,
        'role' => CoachMessageRole::User,
        'content' => 'Help with Python',
    ]);

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
    $conversation = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Test message',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->call('selectConversation', $conversation->id)
        ->assertDispatched('conversation-selected', conversationId: $conversation->id);
});

it('highlights the current active conversation', function () {
    $user = User::factory()->create();
    $conversation = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Test message',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, [
            'mode' => CoachMode::Personal,
            'currentConversationId' => $conversation->id,
        ])
        ->assertSeeHtml('border-accent');
});

it('shows empty state when search has no results', function () {
    $user = User::factory()->create();
    $conversation = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Test message',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationHistory::class, ['mode' => CoachMode::Personal])
        ->set('search', 'nonexistent query')
        ->assertSee('No conversations match your search');
});
