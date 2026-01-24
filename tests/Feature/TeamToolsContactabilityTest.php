<?php

use App\Models\Team;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\TeamTools\GetTeamOverview;

beforeEach(function () {
    $this->manager = User::factory()->create(['coach_contactable' => true]);
    $this->team = Team::factory()->create(['manager_id' => $this->manager->id]);
    $this->context = new CoachContext;
    $this->context->setUser($this->manager);
    $this->context->setTeam($this->team);
});

it('shows people with coach_contactable=true normally', function () {
    $contactablePerson = User::factory()->create(['coach_contactable' => true]);
    $this->team->members()->attach($contactablePerson);

    $tool = new GetTeamOverview($this->context);
    $result = json_decode($tool(), true);

    $person = collect($result['people'])->firstWhere('name', $contactablePerson->full_name);
    expect($person)->not->toBeNull();
    expect($person)->not->toHaveKey('contact_note');
});

it('shows direct reports normally regardless of coach_contactable flag', function () {
    $directReport = User::factory()->create(['coach_contactable' => false]);
    $this->team->members()->attach($directReport);

    $tool = new GetTeamOverview($this->context);
    $result = json_decode($tool(), true);

    $person = collect($result['people'])->firstWhere('name', $directReport->full_name);
    expect($person)->not->toBeNull();
    expect($person)->not->toHaveKey('contact_note');
});

it('adds manager contact note for non-direct-reports with coach_contactable=false', function () {
    $theirManager = User::factory()->create();
    $theirTeam = Team::factory()->create(['manager_id' => $theirManager->id]);

    $nonContactablePerson = User::factory()->create(['coach_contactable' => false]);
    $theirTeam->members()->attach($nonContactablePerson);

    $viewingUser = User::factory()->create();
    $viewingContext = new CoachContext;
    $viewingContext->setUser($viewingUser);
    $viewingContext->setTeam($theirTeam);

    $tool = new GetTeamOverview($viewingContext);
    $result = json_decode($tool(), true);

    $person = collect($result['people'])->firstWhere('name', $nonContactablePerson->full_name);
    expect($person)->not->toBeNull();
    expect($person)->toHaveKey('contact_note');
    expect($person['contact_note'])->toContain($theirManager->full_name);
});

it('works correctly when person is in multiple teams', function () {
    $sharedPerson = User::factory()->create(['coach_contactable' => false]);

    $this->team->members()->attach($sharedPerson);

    $otherManager = User::factory()->create();
    $otherTeam = Team::factory()->create(['manager_id' => $otherManager->id]);
    $otherTeam->members()->attach($sharedPerson);

    $tool = new GetTeamOverview($this->context);
    $result = json_decode($tool(), true);

    $person = collect($result['people'])->firstWhere('name', $sharedPerson->full_name);
    expect($person)->not->toBeNull();
    expect($person)->not->toHaveKey('contact_note');
});

it('shows person normally when they are managed by current user in any of their teams', function () {
    $person = User::factory()->create(['coach_contactable' => false]);

    $this->team->members()->attach($person);

    $unrelatedManager = User::factory()->create();
    $unrelatedTeam = Team::factory()->create(['manager_id' => $unrelatedManager->id]);
    $unrelatedTeam->members()->attach($person);

    $tool = new GetTeamOverview($this->context);
    $result = json_decode($tool(), true);

    $personData = collect($result['people'])->firstWhere('name', $person->full_name);
    expect($personData)->not->toHaveKey('contact_note');
});
