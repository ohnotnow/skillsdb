<?php

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\Tools\FindExperts;
use App\Services\SkillsCoach\Tools\FindSkillSharers;
use App\Services\SkillsCoach\Tools\GetUserProfile;

beforeEach(function () {
    $this->context = app(CoachContext::class);
});

describe('FindExperts', function () {
    it('finds users with High proficiency in a skill', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $expert = User::factory()->create(['coach_contactable' => true]);
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);

        $expert->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('Docker'), true);

        expect($result['skill'])->toBe('Docker');
        expect($result['count'])->toBe(1);
        expect($result['experts'][0]['name'])->toBe($expert->full_name);
    });

    it('excludes users who have opted out of coach recommendations', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $expertOptedIn = User::factory()->create(['coach_contactable' => true]);
        $expertOptedOut = User::factory()->create(['coach_contactable' => false]);
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Kubernetes']);

        $expertOptedIn->skills()->attach($skill, ['level' => SkillLevel::High->value]);
        $expertOptedOut->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('Kubernetes'), true);

        expect($result['count'])->toBe(1);
        expect($result['experts'][0]['name'])->toBe($expertOptedIn->full_name);
    });

    it('excludes the current user from results', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create(['coach_contactable' => true]);
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Python']);

        $currentUser->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('Python'), true);

        expect($result['count'])->toBe(0);
    });

    it('returns helpful message when skill not found', function () {
        $currentUser = User::factory()->create();

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('NonexistentSkill'), true);

        expect($result['found'])->toBeFalse();
        expect($result['message'])->toContain('NonexistentSkill');
    });
});

describe('FindSkillSharers', function () {
    it('finds users at any proficiency level', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Laravel']);

        $lowUser = User::factory()->create(['coach_contactable' => true]);
        $mediumUser = User::factory()->create(['coach_contactable' => true]);
        $highUser = User::factory()->create(['coach_contactable' => true]);

        $lowUser->skills()->attach($skill, ['level' => SkillLevel::Low->value]);
        $mediumUser->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        $highUser->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindSkillSharers::class);
        $result = json_decode($tool('Laravel'), true);

        expect($result['count'])->toBe(3);
        // Should be sorted by level (High first)
        expect($result['sharers'][0]['level'])->toBe('High');
    });

    it('respects opt-out settings', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Vue']);

        $contactable = User::factory()->create(['coach_contactable' => true]);
        $notContactable = User::factory()->create(['coach_contactable' => false]);

        $contactable->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        $notContactable->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindSkillSharers::class);
        $result = json_decode($tool('Vue'), true);

        expect($result['count'])->toBe(1);
    });
});

describe('GetUserProfile', function () {
    it('returns current user skill distribution', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $skills = Skill::factory()->approved($admin)->count(3)->create();
        $user->skills()->attach($skills[0], ['level' => SkillLevel::Low->value]);
        $user->skills()->attach($skills[1], ['level' => SkillLevel::Medium->value]);
        $user->skills()->attach($skills[2], ['level' => SkillLevel::High->value]);

        $this->context->setUser($user);
        $tool = app(GetUserProfile::class);
        $result = json_decode($tool(), true);

        expect($result['name'])->toBe($user->full_name);
        expect($result['total_skills'])->toBe(3);
        expect($result['distribution']['low'])->toBe(1);
        expect($result['distribution']['medium'])->toBe(1);
        expect($result['distribution']['high'])->toBe(1);
    });
});
