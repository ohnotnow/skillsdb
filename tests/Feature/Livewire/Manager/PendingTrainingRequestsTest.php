<?php

use App\Enums\EnrollmentStatus;
use App\Livewire\Manager\PendingTrainingRequests;
use App\Mail\TrainingRequestApproved;
use App\Mail\TrainingRequestRejected;
use App\Models\Team;
use App\Models\TrainingCourse;
use App\Models\TrainingCourseUser;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('is not accessible by non-managers', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PendingTrainingRequests::class)
        ->assertForbidden();
});

it('is accessible by team managers', function () {
    $manager = User::factory()->create();
    Team::factory()->create(['manager_id' => $manager->id]);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->assertSuccessful();
});

it('shows pending requests from managed teams', function () {
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create(['name' => 'Laravel Advanced', 'cost' => '500']);
    $teamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->assertSee('Laravel Advanced')
        ->assertSee('John Doe');
});

it('does not show requests from other teams', function () {
    $manager = User::factory()->create();
    Team::factory()->create(['manager_id' => $manager->id]);
    $otherManager = User::factory()->create();
    $otherTeam = Team::factory()->create(['manager_id' => $otherManager->id]);
    $otherTeamMember = User::factory()->create(['forenames' => 'Other', 'surname' => 'Person']);
    $otherTeamMember->teams()->attach($otherTeam);
    $course = TrainingCourse::factory()->create(['name' => 'Secret Course', 'cost' => '500']);
    $otherTeamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->assertDontSee('Secret Course')
        ->assertDontSee('Other Person');
});

it('can approve a pending request', function () {
    Mail::fake();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create(['cost' => '500']);
    $teamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);
    $enrollment = TrainingCourseUser::where('user_id', $teamMember->id)->first();

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('approve', $enrollment->id);

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Booked);
    expect($enrollment->approved_by)->toBe($manager->id);
    expect($enrollment->approved_at)->not->toBeNull();
});

it('sends approval email to user when approved', function () {
    Mail::fake();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create(['cost' => '500']);
    $teamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);
    $enrollment = TrainingCourseUser::where('user_id', $teamMember->id)->first();

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('approve', $enrollment->id);

    Mail::assertQueued(TrainingRequestApproved::class, function ($mail) use ($teamMember) {
        return $mail->hasTo($teamMember->email);
    });
});

it('can reject a pending request with reason', function () {
    Mail::fake();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create(['cost' => '500']);
    $teamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);
    $enrollment = TrainingCourseUser::where('user_id', $teamMember->id)->first();

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('reject', $enrollment->id, 'Budget constraints');

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::Rejected);
    expect($enrollment->rejection_reason)->toBe('Budget constraints');
    expect($enrollment->approved_by)->toBe($manager->id);
    expect($enrollment->approved_at)->not->toBeNull();
});

it('sends rejection email to user when rejected', function () {
    Mail::fake();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create(['cost' => '500']);
    $teamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);
    $enrollment = TrainingCourseUser::where('user_id', $teamMember->id)->first();

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('reject', $enrollment->id, 'Not in budget');

    Mail::assertQueued(TrainingRequestRejected::class, function ($mail) use ($teamMember) {
        return $mail->hasTo($teamMember->email);
    });
});

it('cannot approve requests from users not in managed teams', function () {
    Mail::fake();
    $manager = User::factory()->create();
    Team::factory()->create(['manager_id' => $manager->id]);
    $otherManager = User::factory()->create();
    $otherTeam = Team::factory()->create(['manager_id' => $otherManager->id]);
    $otherTeamMember = User::factory()->create();
    $otherTeamMember->teams()->attach($otherTeam);
    $course = TrainingCourse::factory()->create(['cost' => '500']);
    $otherTeamMember->trainingCourses()->attach($course->id, [
        'status' => EnrollmentStatus::PendingApproval,
        'requested_at' => now(),
    ]);
    $enrollment = TrainingCourseUser::where('user_id', $otherTeamMember->id)->first();

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('approve', $enrollment->id)
        ->assertForbidden();

    $enrollment->refresh();
    expect($enrollment->status)->toBe(EnrollmentStatus::PendingApproval);
    Mail::assertNothingQueued();
});

it('shows empty state when no pending requests', function () {
    $manager = User::factory()->create();
    Team::factory()->create(['manager_id' => $manager->id]);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->assertSee('No pending training requests');
});
