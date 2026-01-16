<?php

use App\Enums\CoachMessageRole;
use App\Livewire\SkillsCoach;
use App\Models\CoachConversation;
use App\Models\CoachMessage;
use App\Models\User;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

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

it('can send a message and receive a response', function () {
    $user = User::factory()->create();

    Prism::fake([
        TextResponseFake::make()->withText('I recommend learning Docker next.'),
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'What skills should I learn?')
        ->call('send')
        ->assertSet('prompt', '')
        ->assertSee('What skills should I learn?')
        ->assertSee('I recommend learning Docker next.');
});

it('persists messages to the database', function () {
    $user = User::factory()->create();

    Prism::fake([
        TextResponseFake::make()->withText('Great question about Python!'),
    ]);

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'Tell me about Python')
        ->call('send');

    expect(CoachConversation::where('user_id', $user->id)->count())->toBe(1);
    expect(CoachMessage::count())->toBe(2); // User message + assistant response
});

it('loads existing conversation on mount', function () {
    $user = User::factory()->create();
    $conversation = CoachConversation::factory()->create(['user_id' => $user->id]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::User,
        'content' => 'Previous question',
    ]);
    CoachMessage::factory()->create([
        'coach_conversation_id' => $conversation->id,
        'role' => CoachMessageRole::Assistant,
        'content' => 'Previous answer',
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

    Prism::fake([
        TextResponseFake::make()->withText('Test response'),
    ]);

    $component = Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'Test message')
        ->call('send')
        ->assertSee('Test message');

    $originalConversationId = $component->get('conversationId');

    $component->call('clearChat')
        ->assertDontSee('Test message')
        ->assertSee('your Skills Coach');

    // Should have created a new conversation
    expect($component->get('conversationId'))->not->toBe($originalConversationId);
    expect(CoachConversation::where('user_id', $user->id)->count())->toBe(2);
});

it('shows homepage link with skills coach button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Skills Coach');
});
