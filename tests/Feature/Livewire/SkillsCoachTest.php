<?php

use App\Livewire\SkillsCoach;
use App\Models\User;
use Livewire\Livewire;

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
        ->assertSee('Welcome to Skills Coach')
        ->assertSee('Ask me anything about your skills');
});

it('can send a message and receive a response', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'What skills should I learn?')
        ->call('send')
        ->assertSet('prompt', '')
        ->assertSee('What skills should I learn?');
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

it('can clear chat messages', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(SkillsCoach::class)
        ->set('prompt', 'Test message')
        ->call('send')
        ->assertSee('Test message');

    $component->call('clearChat')
        ->assertDontSee('Test message')
        ->assertSee('Welcome to Skills Coach');
});

it('shows homepage link with skills coach button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Skills Coach');
});
