<?php

use App\Livewire\Admin\TrainingCoursesManager;
use App\Models\Skill;
use App\Models\TrainingCourse;
use App\Models\TrainingSupplier;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get('/admin/training')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/training')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/training')
        ->assertSuccessful()
        ->assertSeeLivewire(TrainingCoursesManager::class);
});

it('displays all training courses', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->create(['name' => 'AWS Solutions Architect']);
    TrainingCourse::factory()->create(['name' => 'Kubernetes Fundamentals']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSee('AWS Solutions Architect')
        ->assertSee('Kubernetes Fundamentals');
});

it('can search courses by name', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->create(['name' => 'AWS Solutions Architect']);
    TrainingCourse::factory()->create(['name' => 'Kubernetes Fundamentals']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSee('AWS Solutions Architect')
        ->assertSee('Kubernetes Fundamentals')
        ->set('search', 'AWS')
        ->assertSee('AWS Solutions Architect')
        ->assertDontSee('Kubernetes Fundamentals');
});

it('can search courses by supplier', function () {
    $admin = User::factory()->admin()->create();
    $supplier = TrainingSupplier::factory()->create(['name' => 'Pluralsight']);
    TrainingCourse::factory()->create(['name' => 'AWS Course', 'training_supplier_id' => $supplier->id]);
    TrainingCourse::factory()->create(['name' => 'Docker Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->set('search', 'Pluralsight')
        ->assertSee('AWS Course')
        ->assertDontSee('Docker Course');
});

it('can create a new course', function () {
    $admin = User::factory()->admin()->create();
    $supplier = TrainingSupplier::factory()->create(['name' => 'Udemy']);
    $skill = Skill::factory()->approved()->create(['name' => 'Docker']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openCreateModal')
        ->assertSet('editingCourse.id', null)
        ->set('editingCourse.name', 'Docker Mastery')
        ->set('editingCourse.description', 'Learn Docker from scratch')
        ->set('editingCourse.prerequisites', 'Basic Linux knowledge')
        ->set('editingCourse.cost', '99.99')
        ->set('editingCourse.offers_certification', true)
        ->set('editingCourse.training_supplier_id', $supplier->id)
        ->set('editingCourse.skill_ids', [$skill->id])
        ->call('saveCourse')
        ->assertHasNoErrors();

    $course = TrainingCourse::where('name', 'Docker Mastery')->first();
    expect($course)->not->toBeNull();
    expect($course->description)->toBe('Learn Docker from scratch');
    expect($course->prerequisites)->toBe('Basic Linux knowledge');
    expect((float) $course->cost)->toBe(99.99);
    expect($course->offers_certification)->toBeTrue();
    expect($course->training_supplier_id)->toBe($supplier->id);
    expect($course->skills->pluck('id')->toArray())->toBe([$skill->id]);
});

it('can create a free course', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openCreateModal')
        ->set('editingCourse.name', 'Free Course')
        ->set('editingCourse.cost', '')
        ->call('saveCourse')
        ->assertHasNoErrors();

    $course = TrainingCourse::where('name', 'Free Course')->first();
    expect($course)->not->toBeNull();
    expect($course->isFree())->toBeTrue();
});

it('validates required fields when creating a course', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openCreateModal')
        ->set('editingCourse.name', '')
        ->call('saveCourse')
        ->assertHasErrors(['editingCourse.name']);

    expect(TrainingCourse::count())->toBe(0);
});

it('prevents duplicate course names', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->create(['name' => 'AWS Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openCreateModal')
        ->set('editingCourse.name', 'AWS Course')
        ->call('saveCourse')
        ->assertHasErrors(['editingCourse.name']);
});

it('can edit a course', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->create([
        'name' => 'Old Name',
        'description' => 'Old description',
    ]);
    $newSupplier = TrainingSupplier::factory()->create(['name' => 'New Supplier']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openEditModal', $course->id)
        ->assertSet('editingCourse.id', $course->id)
        ->assertSet('editingCourse.name', 'Old Name')
        ->set('editingCourse.name', 'New Name')
        ->set('editingCourse.description', 'New description')
        ->set('editingCourse.training_supplier_id', $newSupplier->id)
        ->call('saveCourse')
        ->assertHasNoErrors();

    $course->refresh();
    expect($course->name)->toBe('New Name');
    expect($course->description)->toBe('New description');
    expect($course->training_supplier_id)->toBe($newSupplier->id);
});

it('allows editing a course to keep its own name', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->create(['name' => 'AWS Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openEditModal', $course->id)
        ->set('editingCourse.description', 'Updated description')
        ->call('saveCourse')
        ->assertHasNoErrors();

    expect($course->fresh()->description)->toBe('Updated description');
});

it('can update course skills', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);
    $skill1 = Skill::factory()->approved()->create(['name' => 'Docker']);
    $skill2 = Skill::factory()->approved()->create(['name' => 'Kubernetes']);
    $course->skills()->attach($skill1->id);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openEditModal', $course->id)
        ->assertSet('editingCourse.skill_ids', [$skill1->id])
        ->set('editingCourse.skill_ids', [$skill2->id])
        ->call('saveCourse')
        ->assertHasNoErrors();

    expect($course->fresh()->skills->pluck('id')->toArray())->toBe([$skill2->id]);
});

it('can deactivate a course', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('deactivateCourse', $course->id);

    expect($course->fresh()->is_active)->toBeFalse();
});

it('can create a supplier inline', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openCreateModal')
        ->set('supplierSearchTerm', 'New Training Co')
        ->call('createSupplierInline')
        ->assertHasNoErrors();

    $supplier = TrainingSupplier::where('name', 'New Training Co')->first();
    expect($supplier)->not->toBeNull();
});

it('does not create supplier inline with empty name', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->call('openCreateModal')
        ->set('supplierSearchTerm', '')
        ->call('createSupplierInline');

    expect(TrainingSupplier::count())->toBe(0);
});

it('shows skill count for each course', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);
    $skills = Skill::factory()->approved()->count(3)->create();
    $course->skills()->attach($skills->pluck('id'));

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSeeHtml('data-test="course-'.$course->id.'-skills-count"');
});

it('shows enrollment count for each course', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);
    $users = User::factory()->count(2)->create();
    foreach ($users as $user) {
        $course->users()->attach($user->id, ['status' => 1]);
    }

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSeeHtml('data-test="course-'.$course->id.'-users-count"');
});

it('shows free badge for free courses', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->free()->create(['name' => 'Free Training']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSee('Free Training')
        ->assertSee('Free');
});

it('shows certification badge for certified courses', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->certified()->create(['name' => 'Certified Training']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSee('Certified Training');
});

it('displays supplier name in table', function () {
    $admin = User::factory()->admin()->create();
    $supplier = TrainingSupplier::factory()->create(['name' => 'Pluralsight']);
    TrainingCourse::factory()->withSupplier($supplier)->create(['name' => 'Test Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSee('Test Course')
        ->assertSee('Pluralsight');
});

it('can reactivate an inactive course', function () {
    $admin = User::factory()->admin()->create();
    $course = TrainingCourse::factory()->inactive()->create(['name' => 'Inactive Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->set('showInactive', true)
        ->call('reactivateCourse', $course->id);

    expect($course->fresh()->is_active)->toBeTrue();
});

it('hides inactive courses by default', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->create(['name' => 'Active Course']);
    TrainingCourse::factory()->inactive()->create(['name' => 'Inactive Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->assertSee('Active Course')
        ->assertDontSee('Inactive Course');
});

it('shows inactive courses when filter is enabled', function () {
    $admin = User::factory()->admin()->create();
    TrainingCourse::factory()->create(['name' => 'Active Course']);
    TrainingCourse::factory()->inactive()->create(['name' => 'Inactive Course']);

    Livewire::actingAs($admin)
        ->test(TrainingCoursesManager::class)
        ->set('showInactive', true)
        ->assertSee('Active Course')
        ->assertSee('Inactive Course')
        ->assertSee('Inactive');
});
