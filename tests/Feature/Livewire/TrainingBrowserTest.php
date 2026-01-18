<?php

use App\Enums\EnrollmentStatus;
use App\Enums\TrainingRating;
use App\Livewire\TrainingBrowser;
use App\Models\Skill;
use App\Models\TrainingCourse;
use App\Models\TrainingSupplier;
use App\Models\User;
use Livewire\Livewire;

it('displays active courses', function () {
    $user = User::factory()->create();
    TrainingCourse::factory()->create(['name' => 'Laravel Basics']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Laravel Basics');
});

it('does not display inactive courses', function () {
    $user = User::factory()->create();
    TrainingCourse::factory()->create(['name' => 'Active Course']);
    TrainingCourse::factory()->inactive()->create(['name' => 'Inactive Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Active Course')
        ->assertDontSee('Inactive Course');
});

it('displays course supplier name', function () {
    $user = User::factory()->create();
    $supplier = TrainingSupplier::factory()->create(['name' => 'Acme Training']);
    TrainingCourse::factory()->withSupplier($supplier)->create(['name' => 'Test Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Acme Training');
});

it('shows free badge for free courses', function () {
    $user = User::factory()->create();
    TrainingCourse::factory()->free()->create(['name' => 'Free Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Free');
});

it('shows certified badge for courses offering certification', function () {
    $user = User::factory()->create();
    TrainingCourse::factory()->certified()->create(['name' => 'Certified Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Certified');
});

it('displays related skills for courses', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create(['name' => 'PHP']);
    $course = TrainingCourse::factory()->create(['name' => 'PHP Fundamentals']);
    $course->skills()->attach($skill);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('PHP');
});

it('displays aggregated ratings', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Rated Course']);

    $userWithGoodRating = User::factory()->create();
    $userWithGoodRating->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Completed,
        'rating' => TrainingRating::Good,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('1'); // good_count = 1
});

it('can search courses by name', function () {
    $user = User::factory()->create();
    TrainingCourse::factory()->create(['name' => 'Laravel Course']);
    TrainingCourse::factory()->create(['name' => 'Vue Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Laravel Course')
        ->assertSee('Vue Course')
        ->set('search', 'Laravel')
        ->assertSee('Laravel Course')
        ->assertDontSee('Vue Course');
});

it('can search courses by description', function () {
    $user = User::factory()->create();
    TrainingCourse::factory()->create([
        'name' => 'Course One',
        'description' => 'Learn about databases',
    ]);
    TrainingCourse::factory()->create([
        'name' => 'Course Two',
        'description' => 'Learn about testing',
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->set('search', 'databases')
        ->assertSee('Course One')
        ->assertDontSee('Course Two');
});

it('can search courses by supplier name', function () {
    $user = User::factory()->create();
    $supplierA = TrainingSupplier::factory()->create(['name' => 'Acme Training']);
    $supplierB = TrainingSupplier::factory()->create(['name' => 'Beta Corp']);
    TrainingCourse::factory()->withSupplier($supplierA)->create(['name' => 'Acme Course']);
    TrainingCourse::factory()->withSupplier($supplierB)->create(['name' => 'Beta Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->set('search', 'Acme')
        ->assertSee('Acme Course')
        ->assertDontSee('Beta Course');
});

it('can filter to show only enrolled courses', function () {
    $user = User::factory()->create();
    $enrolledCourse = TrainingCourse::factory()->create(['name' => 'Enrolled Course']);
    TrainingCourse::factory()->create(['name' => 'Not Enrolled Course']);

    $user->trainingCourses()->attach($enrolledCourse->id, [
        'status' => EnrollmentStatus::Booked,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('Enrolled Course')
        ->assertSee('Not Enrolled Course')
        ->set('showMyEnrollments', true)
        ->assertSee('Enrolled Course')
        ->assertDontSee('Not Enrolled Course');
});

it('can enroll in a course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('enroll', $course->id);

    expect($user->fresh()->trainingCourses)->toHaveCount(1);
    expect($user->trainingCourses->first()->pivot->status)->toBe(EnrollmentStatus::Booked);
});

it('shows booked status after enrollment', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('enroll', $course->id)
        ->assertSee('Booked');
});

it('cannot enroll twice in the same course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Booked,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('enroll', $course->id);

    expect($user->fresh()->trainingCourses)->toHaveCount(1);
});

it('can mark a course as completed', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Booked,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('markCompleted', $course->id);

    expect($user->fresh()->trainingCourses->first()->pivot->status)->toBe(EnrollmentStatus::Completed);
});

it('shows completed status after marking complete', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Booked,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('markCompleted', $course->id)
        ->assertSee('Completed');
});

it('can unenroll from a booked course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Booked,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('unenroll', $course->id);

    expect($user->fresh()->trainingCourses)->toHaveCount(0);
});

it('cannot unenroll from a completed course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Completed,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('unenroll', $course->id);

    expect($user->fresh()->trainingCourses)->toHaveCount(1);
});

it('can rate a completed course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Completed,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('setRating', $course->id, TrainingRating::Good->value);

    expect($user->fresh()->trainingCourses->first()->pivot->rating)->toBe(TrainingRating::Good);
});

it('cannot rate a non-completed course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Booked,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('setRating', $course->id, TrainingRating::Good->value);

    expect($user->fresh()->trainingCourses->first()->pivot->rating)->toBeNull();
});

it('can change rating on a completed course', function () {
    $user = User::factory()->create();
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    $user->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::Completed,
        'rating' => TrainingRating::Bad,
    ]);

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->call('setRating', $course->id, TrainingRating::Good->value);

    expect($user->fresh()->trainingCourses->first()->pivot->rating)->toBe(TrainingRating::Good);
});

it('shows no courses message when no courses match filters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TrainingBrowser::class)
        ->assertSee('No courses found');
});
