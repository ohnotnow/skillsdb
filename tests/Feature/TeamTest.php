<?php

use App\Models\Team;
use App\Models\User;

it('can create a team', function () {
    $team = Team::factory()->create([
        'name' => 'Infrastructure',
        'description' => 'Server and network team',
    ]);

    expect($team->name)->toBe('Infrastructure');
    expect($team->description)->toBe('Server and network team');
});

it('can have members', function () {
    $team = Team::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $team->members()->attach([$user1->id, $user2->id]);

    expect($team->members)->toHaveCount(2);
    expect($team->members->pluck('id')->toArray())->toContain($user1->id, $user2->id);
});

it('can have a manager', function () {
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);

    expect($team->manager)->not->toBeNull();
    expect($team->manager->id)->toBe($manager->id);
});

it('manager can also be a member', function () {
    $manager = User::factory()->create();
    $team = Team::factory()->create(['manager_id' => $manager->id]);
    $team->members()->attach($manager->id);

    expect($team->members)->toHaveCount(1);
    expect($team->manager->id)->toBe($manager->id);
});

describe('User team relationships', function () {
    it('can belong to teams', function () {
        $user = User::factory()->create();
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        $team1->members()->attach($user->id);
        $team2->members()->attach($user->id);

        expect($user->teams)->toHaveCount(2);
    });

    it('can get managed teams', function () {
        $user = User::factory()->create();
        $managedTeam = Team::factory()->create(['manager_id' => $user->id]);
        $memberTeam = Team::factory()->create();
        $memberTeam->members()->attach($user->id);

        expect($user->managedTeams)->toHaveCount(1);
        expect($user->managedTeams->first()->id)->toBe($managedTeam->id);
    });

    it('knows if user is a team manager', function () {
        $manager = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['manager_id' => $manager->id]);
        $team->members()->attach([$manager->id, $member->id]);

        expect($manager->isTeamManager())->toBeTrue();
        expect($member->isTeamManager())->toBeFalse();
    });

    it('returns false for user with no teams', function () {
        $user = User::factory()->create();

        expect($user->isTeamManager())->toBeFalse();
    });
});

it('cascades delete when team is deleted', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $team->members()->attach($user->id);

    expect($user->teams)->toHaveCount(1);

    $team->delete();

    $user->refresh();
    expect($user->teams)->toHaveCount(0);
});
