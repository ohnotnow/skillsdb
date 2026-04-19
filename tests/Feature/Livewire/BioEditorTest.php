<?php

use App\Livewire\BioEditor;
use App\Models\User;
use Livewire\Livewire;

it('shows the about me button on the homepage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSeeLivewire(BioEditor::class)
        ->assertSee('About Me');
});

it('loads the existing bio when the modal opens', function () {
    $user = User::factory()->create(['bio' => 'I like raspberry pis.']);

    Livewire::actingAs($user)
        ->test(BioEditor::class)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->assertSet('bio', 'I like raspberry pis.');
});

it('can save a bio', function () {
    $user = User::factory()->create(['bio' => null]);

    Livewire::actingAs($user)
        ->test(BioEditor::class)
        ->call('openModal')
        ->set('bio', 'Messing about with ESP32s in my spare time.')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect($user->fresh()->bio)->toBe('Messing about with ESP32s in my spare time.');
});

it('stores a cleared bio as null', function () {
    $user = User::factory()->create(['bio' => 'something old']);

    Livewire::actingAs($user)
        ->test(BioEditor::class)
        ->call('openModal')
        ->set('bio', '')
        ->call('save');

    expect($user->fresh()->bio)->toBeNull();
});

it('explains the privacy implications when editing', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BioEditor::class)
        ->set('showModal', true)
        ->assertSee('private')
        ->assertSee('Skills Coach')
        ->assertSee('Team Coach')
        ->assertSee('external AI');
});

it('validates that the bio is not too long', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BioEditor::class)
        ->set('bio', str_repeat('a', 2001))
        ->call('save')
        ->assertHasErrors(['bio']);

    expect($user->fresh()->bio)->toBeNull();
});

it('does not expose another users bio on the homepage', function () {
    $user = User::factory()->create(['bio' => 'my private bio']);
    User::factory()->create(['bio' => 'another persons private thoughts']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertDontSee('another persons private thoughts');
});
