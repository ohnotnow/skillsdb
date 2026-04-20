<?php

use App\Ai\Agents\PersonalCoachAgent;
use App\Livewire\SkillsCoach;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createTestConversation(User $user, array $messages = []): AgentConversation
{
    $conversation = AgentConversation::create([
        'id' => (string) Str::uuid7(),
        'user_id' => $user->id,
        'title' => 'Test conversation',
    ]);

    foreach ($messages as $message) {
        AgentConversationMessage::create([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'agent' => PersonalCoachAgent::class,
            'role' => $message['role'],
            'content' => $message['content'],
            'meta' => '[]',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
        ]);
    }

    return $conversation;
}

it('can view the skills coach page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('coach'))
        ->assertSuccessful()
        ->assertSeeLivewire(SkillsCoach::class);
});

it('requires authentication to view the skills coach page', function () {
    $this->get(route('coach'))
        ->assertRedirect();
});

it('displays welcome message when no messages exist', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->assertSee('your Skills Coach')
        ->assertSee('skills to learn');
});

it('can send a message and receive a streamed response', function () {
    $user = User::factory()->create();

    PersonalCoachAgent::fake([
        'I recommend learning Docker next.',
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'What skills should I learn?')
        ->call('send')
        ->assertSet('prompt', '')
        ->assertSet('pendingPrompt', 'What skills should I learn?')
        ->assertSee('What skills should I learn?')
        ->assertJs('$wire.streamResponse()')
        ->call('streamResponse')
        ->assertSet('pendingPrompt', null)
        ->assertSee('I recommend learning Docker next.');
});

it('persists messages to the database after streaming completes', function () {
    $user = User::factory()->create();

    PersonalCoachAgent::fake([
        'Great question about Python!',
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'Tell me about Python')
        ->call('send')
        ->call('streamResponse');

    expect(AgentConversation::where('user_id', $user->id)->count())->toBe(1);
    expect(AgentConversationMessage::count())->toBe(2); // User message + assistant response
});

it('loads existing conversation on mount', function () {
    $user = User::factory()->create();
    $conversation = createTestConversation($user, [
        ['role' => 'user', 'content' => 'Previous question'],
        ['role' => 'assistant', 'content' => 'Previous answer'],
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->assertSee('Previous question')
        ->assertSee('Previous answer');
});

it('validates that prompt is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', '')
        ->call('send')
        ->assertHasErrors(['prompt' => 'required']);
});

it('validates that prompt is not too long', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', str_repeat('a', 1001))
        ->call('send')
        ->assertHasErrors(['prompt' => 'max']);
});

it('can clear chat and start new conversation', function () {
    $user = User::factory()->create();

    PersonalCoachAgent::fake([
        'Test response',
    ]);

    $component = Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'Test message')
        ->call('send')
        ->call('streamResponse')
        ->assertSee('Test message');

    $originalConversationId = $component->get('conversationId');

    $component->call('clearChat')
        ->assertSet('messages', [])
        ->assertSet('pendingPrompt', null)
        ->assertSee('your Skills Coach');

    // conversationId should be null after clearing
    expect($component->get('conversationId'))->toBeNull();
});

it('shows homepage link with skills coach button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Skills Coach');
});

it('can switch to a different conversation', function () {
    $user = User::factory()->create();

    $conversation1 = createTestConversation($user, [
        ['role' => 'user', 'content' => 'First conversation message'],
    ]);

    $conversation2 = createTestConversation($user, [
        ['role' => 'user', 'content' => 'Second conversation message'],
    ]);

    // Start with conversation1 loaded, then switch to conversation2
    $component = Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->call('switchConversation', $conversation1->id)
        ->assertSet('conversationId', $conversation1->id);

    // Verify messages array contains only conversation1's messages
    expect($component->get('messages'))->toHaveCount(1);
    expect($component->get('messages')[0]['content'])->toBe('First conversation message');

    $component->call('switchConversation', $conversation2->id)
        ->assertSet('conversationId', $conversation2->id);

    // Verify messages array now contains only conversation2's messages
    expect($component->get('messages'))->toHaveCount(1);
    expect($component->get('messages')[0]['content'])->toBe('Second conversation message');
});

it('can export conversation as json', function () {
    $user = User::factory()->create();
    $conversation = createTestConversation($user, [
        ['role' => 'user', 'content' => 'Test question'],
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('conversationId', $conversation->id)
        ->call('exportConversation', 'json')
        ->assertFileDownloaded('coach-chat-'.$conversation->created_at->format('Y-m-d').'.json');
});

it('can export conversation as markdown', function () {
    $user = User::factory()->create();
    $conversation = createTestConversation($user, [
        ['role' => 'user', 'content' => 'Test question'],
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('conversationId', $conversation->id)
        ->call('exportConversation', 'markdown')
        ->assertFileDownloaded('coach-chat-'.$conversation->created_at->format('Y-m-d').'.md');
});

it('returns null when exporting with no active conversation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('conversationId', null)
        ->call('exportConversation', 'json')
        ->assertNoRedirect();
});

it('cannot export another users conversation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherConversation = createTestConversation($otherUser, [
        ['role' => 'user', 'content' => 'Secret question'],
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('conversationId', $otherConversation->id)
        ->call('exportConversation', 'json');
})->throws(ModelNotFoundException::class);
