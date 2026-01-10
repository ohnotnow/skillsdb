<?php

use App\Mail\PendingSkillsDigest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends digest email to admins when pending skills exist', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $standardUser = User::factory()->create();
    $pendingSkill = Skill::factory()->pending()->create(['name' => 'Rust']);
    $standardUser->skills()->attach($pendingSkill->id, ['level' => 1]);

    $this->artisan('skills:send-pending-digest')
        ->expectsOutput('Sent pending skills digest to 1 admin(s).')
        ->assertSuccessful();

    Mail::assertQueued(PendingSkillsDigest::class, function ($mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });
});

it('does not send email when no pending skills exist', function () {
    Mail::fake();

    User::factory()->admin()->create();
    Skill::factory()->approved()->create();

    $this->artisan('skills:send-pending-digest')
        ->expectsOutput('No pending skills to report.')
        ->assertSuccessful();

    Mail::assertNotQueued(PendingSkillsDigest::class);
});

it('does not send email to non-admin users', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $standardUser = User::factory()->create();
    $pendingSkill = Skill::factory()->pending()->create();
    $standardUser->skills()->attach($pendingSkill->id, ['level' => 1]);

    $this->artisan('skills:send-pending-digest')->assertSuccessful();

    Mail::assertQueued(PendingSkillsDigest::class, function ($mail) use ($admin, $standardUser) {
        return $mail->hasTo($admin->email) && ! $mail->hasTo($standardUser->email);
    });
});

it('sends email to multiple admins', function () {
    Mail::fake();

    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    $pendingSkill = Skill::factory()->pending()->create();
    User::factory()->create()->skills()->attach($pendingSkill->id, ['level' => 1]);

    $this->artisan('skills:send-pending-digest')
        ->expectsOutput('Sent pending skills digest to 2 admin(s).')
        ->assertSuccessful();

    Mail::assertQueued(PendingSkillsDigest::class, 2);
});

it('includes all pending skills in the email', function () {
    Mail::fake();

    User::factory()->admin()->create();
    $user = User::factory()->create();
    $skill1 = Skill::factory()->pending()->create(['name' => 'Rust']);
    $skill2 = Skill::factory()->pending()->create(['name' => 'Go']);
    $user->skills()->attach([$skill1->id => ['level' => 1], $skill2->id => ['level' => 2]]);

    $this->artisan('skills:send-pending-digest')->assertSuccessful();

    Mail::assertQueued(PendingSkillsDigest::class, function ($mail) {
        return $mail->skills->count() === 2
            && $mail->skills->pluck('name')->contains('Rust')
            && $mail->skills->pluck('name')->contains('Go');
    });
});

it('email subject reflects skill count', function () {
    Mail::fake();

    User::factory()->admin()->create();
    $user = User::factory()->create();
    $skill = Skill::factory()->pending()->create();
    $user->skills()->attach($skill->id, ['level' => 1]);

    $this->artisan('skills:send-pending-digest')->assertSuccessful();

    Mail::assertQueued(PendingSkillsDigest::class, function ($mail) {
        return $mail->envelope()->subject === 'SkillsDB: 1 pending skill to review';
    });
});
