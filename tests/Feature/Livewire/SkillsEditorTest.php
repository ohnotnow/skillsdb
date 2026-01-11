<?php

use App\Enums\SkillLevel;
use App\Livewire\SkillsEditor;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Livewire\Livewire;

it('displays approved skills', function () {
    $user = User::factory()->create();
    $category = SkillCategory::factory()->create(['name' => 'Programming']);
    $skill = Skill::factory()->approved()->create([
        'name' => 'PHP',
        'skill_category_id' => $category->id,
    ]);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->assertSee('PHP')
        ->assertSee('Programming');
});

it('does not display pending skills from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $pendingSkill = Skill::factory()->pending()->create(['name' => 'Rust']);
    $otherUser->skills()->attach($pendingSkill->id, ['level' => SkillLevel::Medium->value]);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->assertDontSee('Rust');
});

it('displays pending skills that the user has added to themselves', function () {
    $user = User::factory()->create();

    $pendingSkill = Skill::factory()->pending()->create(['name' => 'Rust']);
    $user->skills()->attach($pendingSkill->id, ['level' => SkillLevel::Medium->value]);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->assertSee('Rust')
        ->assertSee('Pending');
});

it('can filter skills by search term', function () {
    $user = User::factory()->create();
    Skill::factory()->approved()->create(['name' => 'PHP']);
    Skill::factory()->approved()->create(['name' => 'JavaScript']);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->assertSee('PHP')
        ->assertSee('JavaScript')
        ->set('search', 'PHP')
        ->assertSee('PHP')
        ->assertDontSee('JavaScript');
});

it('can filter to show only my skills', function () {
    $user = User::factory()->create();
    $phpSkill = Skill::factory()->approved()->create(['name' => 'PHP']);
    Skill::factory()->approved()->create(['name' => 'JavaScript']);

    $user->skills()->attach($phpSkill->id, ['level' => SkillLevel::High->value]);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->assertSee('PHP')
        ->assertSee('JavaScript')
        ->set('showMySkillsOnly', true)
        ->assertSee('PHP')
        ->assertDontSee('JavaScript');
});

it('can update skill level', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->call('updateSkillLevel', $skill->id, SkillLevel::High->value);

    expect($user->fresh()->skills)->toHaveCount(1);
    expect($user->fresh()->skills->first()->pivot->level)->toBe(SkillLevel::High);
    expect($user->fresh()->last_updated_skills_at)->not->toBeNull();
});

it('can remove skill by setting level to none', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);
    $user->skills()->attach($skill->id, ['level' => SkillLevel::High->value]);

    expect($user->skills)->toHaveCount(1);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->call('updateSkillLevel', $skill->id, 'none');

    expect($user->fresh()->skills)->toHaveCount(0);
});

it('can suggest a new skill', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->call('openSuggestModal')
        ->assertSet('showSuggestModal', true)
        ->set('newSkillName', 'Kubernetes')
        ->set('newSkillDescription', 'Container orchestration platform')
        ->set('newSkillLevel', SkillLevel::Medium->value)
        ->call('suggestSkill')
        ->assertSet('showSuggestModal', false);

    $skill = Skill::where('name', 'Kubernetes')->first();
    expect($skill)->not->toBeNull();
    expect($skill->isPending())->toBeTrue();
    expect($skill->description)->toBe('Container orchestration platform');
    expect($user->fresh()->skills)->toHaveCount(1);
    expect($user->skills->first()->id)->toBe($skill->id);
});

it('validates required fields when suggesting a skill', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->call('openSuggestModal')
        ->set('newSkillName', '')
        ->set('newSkillLevel', '')
        ->call('suggestSkill')
        ->assertHasErrors(['newSkillName', 'newSkillLevel']);

    expect(Skill::count())->toBe(0);
});

it('prevents duplicate skill names when suggesting', function () {
    $user = User::factory()->create();
    Skill::factory()->approved()->create(['name' => 'PHP']);

    Livewire::actingAs($user)
        ->test(SkillsEditor::class)
        ->call('openSuggestModal')
        ->set('newSkillName', 'PHP')
        ->set('newSkillLevel', SkillLevel::Medium->value)
        ->call('suggestSkill')
        ->assertHasErrors(['newSkillName']);
});
