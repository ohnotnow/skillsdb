<?php

use App\Enums\SkillLevel;
use App\Livewire\Admin\SkillsMatrix;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get('/admin/matrix')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/matrix')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/matrix')
        ->assertSuccessful()
        ->assertSeeLivewire(SkillsMatrix::class);
});

it('displays all users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);
    Skill::factory()->approved()->create();

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee('Alice Smith')
        ->assertSee('Bob Jones');
});

it('displays all approved skills as columns', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'Docker']);
    Skill::factory()->approved()->create(['name' => 'Kubernetes']);

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee('Docker')
        ->assertSee('Kubernetes');
});

it('does not display pending skills', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'Docker']);
    Skill::factory()->pending()->create(['name' => 'PendingSkill']);

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee('Docker')
        ->assertDontSee('PendingSkill');
});

it('shows skill levels for users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['forenames' => 'Test', 'surname' => 'User']);
    $skill = Skill::factory()->approved()->create(['name' => 'Docker']);

    $user->skills()->attach($skill->id, ['level' => SkillLevel::High->value]);

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee('Test User')
        ->assertSee('Docker')
        ->assertSee('H');
});

it('shows different badge colours for different levels', function () {
    $admin = User::factory()->admin()->create();
    $userLow = User::factory()->create(['forenames' => 'Low', 'surname' => 'User']);
    $userMedium = User::factory()->create(['forenames' => 'Medium', 'surname' => 'User']);
    $userHigh = User::factory()->create(['forenames' => 'High', 'surname' => 'User']);

    $skill = Skill::factory()->approved()->create(['name' => 'TestSkill']);

    $userLow->skills()->attach($skill->id, ['level' => SkillLevel::Low->value]);
    $userMedium->skills()->attach($skill->id, ['level' => SkillLevel::Medium->value]);
    $userHigh->skills()->attach($skill->id, ['level' => SkillLevel::High->value]);

    $response = Livewire::actingAs($admin)->test(SkillsMatrix::class);

    $response->assertSee('L');
    $response->assertSee('M');
    $response->assertSee('H');
});

it('shows matrix when only admin exists', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create();

    // Delete all users except the admin
    User::where('id', '!=', $admin->id)->delete();

    // Still shows the admin at least
    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee($admin->full_name);
});

it('shows empty state when no approved skills exist', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee('No data to display')
        ->assertSee('No approved skills exist yet');
});

it('displays the legend', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create();

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->assertSee('Low')
        ->assertSee('Medium')
        ->assertSee('High');
});

it('can filter by a single skill', function () {
    $admin = User::factory()->admin()->create();
    $docker = Skill::factory()->approved()->create(['name' => 'Docker']);
    Skill::factory()->approved()->create(['name' => 'Git']);

    $component = Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->set('selectedSkills', [$docker->id]);

    $skills = $component->instance()->skills;
    expect($skills)->toHaveCount(1);
    expect($skills->first()->name)->toBe('Docker');
});

it('can filter by multiple skills', function () {
    $admin = User::factory()->admin()->create();
    $docker = Skill::factory()->approved()->create(['name' => 'Docker']);
    $git = Skill::factory()->approved()->create(['name' => 'Git']);
    Skill::factory()->approved()->create(['name' => 'PHP']);

    $component = Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->set('selectedSkills', [$docker->id, $git->id]);

    $skills = $component->instance()->skills;
    expect($skills)->toHaveCount(2);
    expect($skills->pluck('name')->toArray())->toContain('Docker', 'Git');
    expect($skills->pluck('name')->toArray())->not->toContain('PHP');
});

it('can filter by a single user', function () {
    $admin = User::factory()->admin()->create();
    $alice = User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);
    Skill::factory()->approved()->create();

    $component = Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->set('selectedUsers', [$alice->id]);

    $users = $component->instance()->users;
    expect($users)->toHaveCount(1);
    expect($users->first()->full_name)->toBe('Alice Smith');
});

it('can filter by multiple users', function () {
    $admin = User::factory()->admin()->create();
    $alice = User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    $bob = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);
    User::factory()->create(['forenames' => 'Charlie', 'surname' => 'Brown']);
    Skill::factory()->approved()->create();

    $component = Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->set('selectedUsers', [$alice->id, $bob->id]);

    $users = $component->instance()->users;
    expect($users)->toHaveCount(2);
    expect($users->pluck('full_name')->toArray())->toContain('Alice Smith', 'Bob Jones');
    expect($users->pluck('full_name')->toArray())->not->toContain('Charlie Brown');
});

it('can combine skill and user filters', function () {
    $admin = User::factory()->admin()->create();
    $alice = User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);
    $docker = Skill::factory()->approved()->create(['name' => 'Docker']);
    Skill::factory()->approved()->create(['name' => 'Git']);

    $component = Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->set('selectedUsers', [$alice->id])
        ->set('selectedSkills', [$docker->id]);

    $users = $component->instance()->users;
    $skills = $component->instance()->skills;

    expect($users)->toHaveCount(1);
    expect($users->first()->full_name)->toBe('Alice Smith');
    expect($skills)->toHaveCount(1);
    expect($skills->first()->name)->toBe('Docker');
});

it('shows all data when filters are empty', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);
    Skill::factory()->approved()->create(['name' => 'Docker']);
    Skill::factory()->approved()->create(['name' => 'Git']);

    Livewire::actingAs($admin)
        ->test(SkillsMatrix::class)
        ->set('selectedUsers', [])
        ->set('selectedSkills', [])
        ->assertSee('Alice Smith')
        ->assertSee('Bob Jones')
        ->assertSee('Docker')
        ->assertSee('Git');
});
