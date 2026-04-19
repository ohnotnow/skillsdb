<?php

use App\Ai\Tools\PersonalTools\FindByInterest;
use App\Ai\Tools\PersonalTools\FindExperts;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use Laravel\Ai\Tools\Request;

it('exposes a users bio to the coach when returning them as an expert', function () {
    $admin = User::factory()->admin()->create();
    $currentUser = User::factory()->create();
    $expert = User::factory()->create([
        'coach_contactable' => true,
        'bio' => 'Used to run a home k3s cluster for fun.',
    ]);
    $skill = Skill::factory()->approved($admin)->create(['name' => 'Kubernetes']);
    $expert->skills()->attach($skill, ['level' => SkillLevel::High->value]);

    app(CoachContext::class)->setUser($currentUser);
    $result = json_decode(app(FindExperts::class)->handle(new Request(['skill_name' => 'Kubernetes'])), true);

    expect($result['experts'][0]['bio'])->toBe('Used to run a home k3s cluster for fun.');
});

it('finds colleagues by an interest mentioned in their bio', function () {
    $currentUser = User::factory()->create();
    $tinkerer = User::factory()->create([
        'coach_contactable' => true,
        'bio' => 'I mess about with ESP32s for home automation.',
    ]);
    User::factory()->create([
        'coach_contactable' => true,
        'bio' => 'Mostly into woodworking and reading novels.',
    ]);

    app(CoachContext::class)->setUser($currentUser);
    $result = json_decode(app(FindByInterest::class)->handle(new Request(['interest' => 'ESP32'])), true);

    expect($result['count'])->toBe(1)
        ->and($result['matches'][0]['name'])->toBe($tinkerer->full_name)
        ->and($result['matches'][0]['bio'])->toContain('ESP32');
});

it('excludes users who have opted out of coach recommendations from the interest search', function () {
    $currentUser = User::factory()->create();
    User::factory()->create([
        'coach_contactable' => false,
        'bio' => 'ESP32 hacker.',
    ]);

    app(CoachContext::class)->setUser($currentUser);
    $result = json_decode(app(FindByInterest::class)->handle(new Request(['interest' => 'ESP32'])), true);

    expect($result['count'])->toBe(0);
});
