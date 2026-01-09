<?php

use App\Livewire\Admin\SkillsManager;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get('/admin/skills')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/skills')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/skills')
        ->assertSuccessful()
        ->assertSeeLivewire(SkillsManager::class);
});

it('displays all skills for admins', function () {
    $admin = User::factory()->admin()->create();
    $approvedSkill = Skill::factory()->approved()->create(['name' => 'PHP']);
    $pendingSkill = Skill::factory()->pending()->create(['name' => 'Rust']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('PHP')
        ->assertSee('Rust');
});

it('shows pending badge for pending skills', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->pending()->create(['name' => 'Rust']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('Rust')
        ->assertSee('Pending');
});

it('can filter to show only pending skills', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'PHP']);
    Skill::factory()->pending()->create(['name' => 'Rust']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('PHP')
        ->assertSee('Rust')
        ->set('showPendingOnly', true)
        ->assertDontSee('PHP')
        ->assertSee('Rust');
});

it('can search skills by name', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'PHP']);
    Skill::factory()->approved()->create(['name' => 'JavaScript']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('PHP')
        ->assertSee('JavaScript')
        ->set('search', 'PHP')
        ->assertSee('PHP')
        ->assertDontSee('JavaScript');
});

it('can search skills by category', function () {
    $admin = User::factory()->admin()->create();
    $category = SkillCategory::factory()->create(['name' => 'Backend']);
    Skill::factory()->approved()->create(['name' => 'PHP', 'skill_category_id' => $category->id]);
    Skill::factory()->approved()->create(['name' => 'JavaScript']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->set('search', 'Backend')
        ->assertSee('PHP')
        ->assertDontSee('JavaScript');
});

it('can create a new skill', function () {
    $admin = User::factory()->admin()->create();
    $category = SkillCategory::factory()->create(['name' => 'DevOps']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('openCreateModal')
        ->assertSet('showSkillModal', true)
        ->assertSet('editingSkillId', null)
        ->set('skillName', 'Kubernetes')
        ->set('skillDescription', 'Container orchestration')
        ->set('skillCategoryId', $category->id)
        ->call('saveSkill')
        ->assertSet('showSkillModal', false)
        ->assertHasNoErrors();

    $skill = Skill::where('name', 'Kubernetes')->first();
    expect($skill)->not->toBeNull();
    expect($skill->isApproved())->toBeTrue();
    expect($skill->approved_by)->toBe($admin->id);
    expect($skill->description)->toBe('Container orchestration');
    expect($skill->skill_category_id)->toBe($category->id);
});

it('validates required fields when creating a skill', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('openCreateModal')
        ->set('skillName', '')
        ->call('saveSkill')
        ->assertHasErrors(['skillName']);

    expect(Skill::count())->toBe(0);
});

it('prevents duplicate skill names', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'PHP']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('openCreateModal')
        ->set('skillName', 'PHP')
        ->call('saveSkill')
        ->assertHasErrors(['skillName']);
});

it('can edit a skill', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP', 'description' => 'Old description']);
    $category = SkillCategory::factory()->create(['name' => 'Backend']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('openEditModal', $skill->id)
        ->assertSet('showSkillModal', true)
        ->assertSet('editingSkillId', $skill->id)
        ->assertSet('skillName', 'PHP')
        ->set('skillName', 'PHP 8')
        ->set('skillDescription', 'New description')
        ->set('skillCategoryId', $category->id)
        ->call('saveSkill')
        ->assertSet('showSkillModal', false)
        ->assertHasNoErrors();

    $skill->refresh();
    expect($skill->name)->toBe('PHP 8');
    expect($skill->description)->toBe('New description');
    expect($skill->skill_category_id)->toBe($category->id);
});

it('allows editing a skill to keep its own name', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('openEditModal', $skill->id)
        ->set('skillDescription', 'Updated description')
        ->call('saveSkill')
        ->assertHasNoErrors();

    expect($skill->fresh()->description)->toBe('Updated description');
});

it('can delete a skill', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('confirmDelete', $skill->id)
        ->assertSet('deletingSkillId', $skill->id)
        ->call('deleteSkill');

    expect(Skill::find($skill->id))->toBeNull();
});

it('can cancel delete', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('confirmDelete', $skill->id)
        ->assertSet('deletingSkillId', $skill->id)
        ->call('cancelDelete')
        ->assertSet('deletingSkillId', null);

    expect(Skill::find($skill->id))->not->toBeNull();
});

it('removes skill from users when deleted', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);
    $user->skills()->attach($skill->id, ['level' => 2]);

    expect($user->skills)->toHaveCount(1);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('confirmDelete', $skill->id)
        ->call('deleteSkill');

    expect($user->fresh()->skills)->toHaveCount(0);
});

it('can approve a pending skill', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->pending()->create(['name' => 'Rust']);

    expect($skill->isPending())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->call('approveSkill', $skill->id);

    $skill->refresh();
    expect($skill->isApproved())->toBeTrue();
    expect($skill->approved_by)->toBe($admin->id);
    expect($skill->approved_at)->not->toBeNull();
});

it('shows user count for each skill', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);

    $users = User::factory()->count(3)->create();
    foreach ($users as $user) {
        $user->skills()->attach($skill->id, ['level' => 2]);
    }

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('3');
});

it('shows pending count badge', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->pending()->count(5)->create();
    Skill::factory()->approved()->count(3)->create();

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('5');
});

it('shows requester name for pending skills', function () {
    $admin = User::factory()->admin()->create();
    $requester = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);
    $pendingSkill = Skill::factory()->pending()->create(['name' => 'Docker']);
    $requester->skills()->attach($pendingSkill->id, ['level' => 2]);

    Livewire::actingAs($admin)
        ->test(SkillsManager::class)
        ->assertSee('Docker')
        ->assertSee('J. Smith');
});
