<?php

use App\Ai\Tools\FindTrainingCourses;
use App\Enums\EnrollmentStatus;
use App\Enums\TrainingRating;
use App\Models\Skill;
use App\Models\TrainingCourse;
use App\Models\TrainingSupplier;
use App\Models\User;
use Laravel\Ai\Tools\Request;

it('finds courses by associated skill name', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);
    $course = TrainingCourse::factory()->withSupplier()->create(['name' => 'Docker Mastery']);
    $course->skills()->attach($skill);

    $unrelatedCourse = TrainingCourse::factory()->create(['name' => 'Cooking 101']);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request(['skill_name' => 'Docker'])), true);

    expect($result['skill'])->toBe('Docker');
    expect($result['count'])->toBe(1);
    expect($result['courses'][0]['name'])->toBe('Docker Mastery');
});

it('searches courses by name or description', function () {
    TrainingCourse::factory()->create(['name' => 'AWS Solutions Architect', 'description' => 'Cloud infrastructure']);
    TrainingCourse::factory()->create(['name' => 'Python Basics', 'description' => 'Intro to programming']);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request(['query' => 'AWS'])), true);

    expect($result['count'])->toBe(1);
    expect($result['courses'][0]['name'])->toBe('AWS Solutions Architect');
});

it('returns all active courses when no params given', function () {
    TrainingCourse::factory()->count(3)->create();

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    expect($result['count'])->toBe(3);
});

it('excludes inactive courses', function () {
    TrainingCourse::factory()->create(['name' => 'Active Course']);
    TrainingCourse::factory()->inactive()->create(['name' => 'Old Course']);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    expect($result['count'])->toBe(1);
    expect($result['courses'][0]['name'])->toBe('Active Course');
});

it('includes rating summary from completed users', function () {
    $course = TrainingCourse::factory()->create();

    // Create users with different ratings
    $goodUser = User::factory()->create();
    $badUser = User::factory()->create();
    $course->users()->attach($goodUser, ['status' => EnrollmentStatus::Completed, 'rating' => TrainingRating::Good]);
    $course->users()->attach($badUser, ['status' => EnrollmentStatus::Completed, 'rating' => TrainingRating::Bad]);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    expect($result['courses'][0]['ratings']['good'])->toBe(1);
    expect($result['courses'][0]['ratings']['bad'])->toBe(1);
    expect($result['courses'][0]['ratings']['total'])->toBe(2);
});

it('includes supplier info and cost', function () {
    $supplier = TrainingSupplier::factory()->create(['name' => 'Pluralsight', 'website' => 'https://pluralsight.com']);
    TrainingCourse::factory()->withSupplier($supplier)->create(['name' => 'K8s Course', 'cost' => '199']);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    expect($result['courses'][0]['supplier'])->toBe('Pluralsight');
    expect($result['courses'][0]['supplier_website'])->toBe('https://pluralsight.com');
    expect($result['courses'][0]['cost'])->toBe('199');
});

it('shows Free for courses with no cost', function () {
    TrainingCourse::factory()->free()->create(['name' => 'Free Workshop']);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    expect($result['courses'][0]['cost'])->toBe('Free');
});

it('returns helpful message when no matching courses found', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved($admin)->create(['name' => 'Obscure Tech']);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request(['skill_name' => 'Obscure Tech'])), true);

    expect($result['courses'])->toBeEmpty();
    expect($result['message'])->toContain('No training courses');
});

it('shows certification flag', function () {
    TrainingCourse::factory()->certified()->create(['name' => 'AWS Cert']);
    TrainingCourse::factory()->create(['name' => 'Casual Course', 'offers_certification' => false]);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    $certified = collect($result['courses'])->firstWhere('name', 'AWS Cert');
    $casual = collect($result['courses'])->firstWhere('name', 'Casual Course');

    expect($certified['offers_certification'])->toBeTrue();
    expect($casual['offers_certification'])->toBeFalse();
});

it('shows completed count', function () {
    $course = TrainingCourse::factory()->create();
    $completedUser = User::factory()->create();
    $bookedUser = User::factory()->create();
    $course->users()->attach($completedUser, ['status' => EnrollmentStatus::Completed]);
    $course->users()->attach($bookedUser, ['status' => EnrollmentStatus::Booked]);

    $tool = app(FindTrainingCourses::class);
    $result = json_decode($tool->handle(new Request), true);

    expect($result['courses'][0]['completed_by'])->toBe(1);
});
