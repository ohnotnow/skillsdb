<?php

use App\Livewire\Admin\UserSkillsEditor;
use App\Livewire\SkillsEditor;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $user = User::factory()->create();

    $this->get("/admin/users/{$user->id}")
        ->assertRedirect();
});

it('requires admin access', function () {
    $regularUser = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($regularUser)
        ->get("/admin/users/{$targetUser->id}")
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertSeeLivewire(UserSkillsEditor::class);
});

it('displays the users name', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['forenames' => 'Test', 'surname' => 'Person']);

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertSee('Test Person');
});

it('embeds the skills editor component for the user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertSeeLivewire(SkillsEditor::class);
});

it('shows approved skills', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    Skill::factory()->approved()->create(['name' => 'PHP']);
    Skill::factory()->approved()->create(['name' => 'JavaScript']);

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertSee('PHP')
        ->assertSee('JavaScript');
});

it('does not show pending skills', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    Skill::factory()->pending()->create(['name' => 'Rust']);

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertDontSee('Rust');
});

it('can assign a skill to the user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    expect($user->skills)->toHaveCount(0);

    Livewire::actingAs($admin)
        ->test(SkillsEditor::class, ['user' => $user])
        ->call('updateSkillLevel', $skill->id, '2');

    expect($user->fresh()->skills)->toHaveCount(1);
    expect($user->fresh()->skills->first()->pivot->level)->toBe(2);
});

it('can update a users skill level', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();
    $user->skills()->attach($skill->id, ['level' => 1]);

    Livewire::actingAs($admin)
        ->test(SkillsEditor::class, ['user' => $user])
        ->call('updateSkillLevel', $skill->id, '3');

    expect($user->fresh()->skills->first()->pivot->level)->toBe(3);
});

it('can remove a skill from a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();
    $user->skills()->attach($skill->id, ['level' => 2]);

    Livewire::actingAs($admin)
        ->test(SkillsEditor::class, ['user' => $user])
        ->call('updateSkillLevel', $skill->id, 'none');

    expect($user->fresh()->skills)->toHaveCount(0);
});

it('updates users last_updated_skills_at when skills are changed', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['last_updated_skills_at' => null]);
    $skill = Skill::factory()->approved()->create();

    expect($user->last_updated_skills_at)->toBeNull();

    Livewire::actingAs($admin)
        ->test(SkillsEditor::class, ['user' => $user])
        ->call('updateSkillLevel', $skill->id, '2');

    expect($user->fresh()->last_updated_skills_at)->not->toBeNull();
});

it('does not show suggest skill button in admin context', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(SkillsEditor::class, ['user' => $user])
        ->assertDontSee('Suggest Skill');
});

it('shows their instead of my in filter label for admin context', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(SkillsEditor::class, ['user' => $user])
        ->assertSee('Show only their skills');
});

it('has a back link to users list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertSee('Back to Users')
        ->assertSee(route('admin.users'));
});
