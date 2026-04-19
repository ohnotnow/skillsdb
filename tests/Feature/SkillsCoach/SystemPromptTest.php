<?php

use App\Models\User;
use App\Services\SkillsCoach\SystemPrompt;

it('includes the users bio in the system prompt', function () {
    $user = User::factory()->create([
        'bio' => 'I tinker with ESP32s and love a good home automation project.',
    ]);

    $prompt = app(SystemPrompt::class)->build($user);

    expect($prompt)->toContain('I tinker with ESP32s and love a good home automation project.');
});

it('notes when the user has not set a bio', function () {
    $user = User::factory()->create(['bio' => null]);

    $prompt = app(SystemPrompt::class)->build($user);

    expect($prompt)->toContain('not provided');
});

it('tells the coach to stay on topic', function () {
    $user = User::factory()->create();

    $prompt = app(SystemPrompt::class)->build($user);

    expect($prompt)->toContain("What's In Scope")
        ->toContain('steer')
        ->toContain('career development');
});
