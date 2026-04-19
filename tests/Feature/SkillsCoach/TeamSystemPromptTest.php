<?php

use App\Models\Team;
use App\Models\User;
use App\Services\SkillsCoach\TeamSystemPrompt;

it('tells the team coach to stay on topic and defer HR matters', function () {
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);

    $prompt = app(TeamSystemPrompt::class)->build($manager, $team);

    expect($prompt)->toContain("What's In Scope")
        ->toContain('HR')
        ->toContain('disciplinary')
        ->toContain('steer');
});
