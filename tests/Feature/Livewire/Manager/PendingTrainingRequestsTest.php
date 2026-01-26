<?php

use App\Enums\EnrollmentStatus;
use App\Livewire\Manager\PendingTrainingRequests;
use App\Mail\TeamMemberEnrolled;
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

it('shows empty state when no pending requests', function () {
    $manager = User::factory()->create();
    Team::factory()->create(['manager_id' => $manager->id]);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->assertSee('No pending training requests');
});

it('shows team members in the enroll tab', function () {
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
    $teamMember->teams()->attach($team);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->set('tab', 'enroll')
        ->assertSee('Alice Smith');
});

it('admin can see all staff in the enroll tab', function () {
    $admin = User::factory()->admin()->create();
    $staffMember = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones', 'is_staff' => true]);

    Livewire::actingAs($admin)
        ->test(PendingTrainingRequests::class)
        ->set('tab', 'enroll')
        ->assertSee('Bob Jones');
});

it('can enroll a team member on courses', function () {
    Mail::fake();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create(['name' => 'Test Course']);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('openEnrollModal', $teamMember->id)
        ->set('coursesToEnroll', [$course->id])
        ->call('enrollTeamMember');

    expect($teamMember->fresh()->trainingCourses)->toHaveCount(1);
    expect($teamMember->trainingCourses->first()->pivot->status)->toBe(EnrollmentStatus::Booked);
    expect($teamMember->trainingCourses->first()->pivot->approved_by)->toBe($manager->id);
});

it('sends email when team member is enrolled by manager', function () {
    Mail::fake();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $course = TrainingCourse::factory()->create();

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('openEnrollModal', $teamMember->id)
        ->set('coursesToEnroll', [$course->id])
        ->call('enrollTeamMember');

    Mail::assertQueued(TeamMemberEnrolled::class, function ($mail) use ($teamMember) {
        return $mail->hasTo($teamMember->email);
    });
});

it('excludes already enrolled courses from available courses', function () {
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $teamMember = User::factory()->create();
    $teamMember->teams()->attach($team);
    $enrolledCourse = TrainingCourse::factory()->create(['name' => 'Already Enrolled']);
    $availableCourse = TrainingCourse::factory()->create(['name' => 'Available Course']);
    $teamMember->trainingCourses()->attach($enrolledCourse->id, ['status' => EnrollmentStatus::Booked]);

    Livewire::actingAs($manager)
        ->test(PendingTrainingRequests::class)
        ->call('openEnrollModal', $teamMember->id)
        ->assertSee('Available Course')
        ->assertDontSee('Already Enrolled');
});
