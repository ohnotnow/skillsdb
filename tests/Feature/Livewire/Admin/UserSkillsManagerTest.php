<?php

use App\Livewire\Admin\UserSkillsManager;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get('/admin/users')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertSuccessful()
        ->assertSeeLivewire(UserSkillsManager::class);
});

it('displays all users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->assertSee('Alice Smith')
        ->assertSee('Bob Jones');
});

it('shows admin badge for admin users', function () {
    $admin = User::factory()->admin()->create(['forenames' => 'Super', 'surname' => 'Admin']);

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->assertSee('Super Admin')
        ->assertSee('Admin');
});

it('shows skills count for each user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $skills = Skill::factory()->approved()->count(3)->create();

    foreach ($skills as $skill) {
        $user->skills()->attach($skill->id, ['level' => 2]);
    }

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->assertSee('3');
});

it('can search users by name', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->assertSee('Alice Smith')
        ->assertSee('Bob Jones')
        ->set('search', 'Alice')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

it('can search users by email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith', 'email' => 'alice@example.com']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones', 'email' => 'bob@example.com']);

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->set('search', 'alice@example')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

it('shows last updated time for users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create([
        'forenames' => 'Recent',
        'surname' => 'User',
        'last_updated_skills_at' => now()->subDays(2),
    ]);

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->assertSee('2 days ago');
});

it('shows never for users who have not updated skills', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create([
        'forenames' => 'New',
        'surname' => 'User',
        'last_updated_skills_at' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(UserSkillsManager::class)
        ->assertSee('Never');
});

it('shows user names as links to their skills page', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['forenames' => 'Test', 'surname' => 'Person']);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertSee('Test Person')
        ->assertSee(route('admin.users.skills', $user));
});
