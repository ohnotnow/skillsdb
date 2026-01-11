<?php

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;

describe('getSkillDistribution', function () {
    it('returns zero counts when user has no skills', function () {
        $user = User::factory()->create();

        $distribution = $user->getSkillDistribution();

        expect($distribution)->toBe([
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'total' => 0,
        ]);
    });

    it('counts skills at each level correctly', function () {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        // Create skills at different levels
        $lowSkills = Skill::factory()->approved($admin)->count(2)->create();
        $mediumSkills = Skill::factory()->approved($admin)->count(3)->create();
        $highSkills = Skill::factory()->approved($admin)->count(1)->create();

        foreach ($lowSkills as $skill) {
            $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);
        }
        foreach ($mediumSkills as $skill) {
            $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        }
        foreach ($highSkills as $skill) {
            $user->skills()->attach($skill, ['level' => SkillLevel::High->value]);
        }

        $distribution = $user->getSkillDistribution();

        expect($distribution)->toBe([
            'low' => 2,
            'medium' => 3,
            'high' => 1,
            'total' => 6,
        ]);
    });
});

describe('hasStaleSkills', function () {
    it('returns true when last_updated_skills_at is null', function () {
        $user = User::factory()->create(['last_updated_skills_at' => null]);

        expect($user->hasStaleSkills())->toBeTrue();
    });

    it('returns true when skills updated more than 4 weeks ago', function () {
        $user = User::factory()->create([
            'last_updated_skills_at' => now()->subWeeks(5),
        ]);

        expect($user->hasStaleSkills())->toBeTrue();
    });

    it('returns false when skills updated within 4 weeks', function () {
        $user = User::factory()->create([
            'last_updated_skills_at' => now()->subWeeks(3),
        ]);

        expect($user->hasStaleSkills())->toBeFalse();
    });
});

describe('getLastUpdatedText', function () {
    it('returns italic never when last_updated_skills_at is null', function () {
        $user = User::factory()->create(['last_updated_skills_at' => null]);

        expect($user->getLastUpdatedText())->toBe('<em>never</em>');
    });

    it('returns italic ages ago when skills are stale', function () {
        $user = User::factory()->create([
            'last_updated_skills_at' => now()->subWeeks(5),
        ]);

        expect($user->getLastUpdatedText())->toBe('<em>ages ago</em>');
    });

    it('returns diffForHumans when skills are fresh', function () {
        $user = User::factory()->create([
            'last_updated_skills_at' => now()->subDays(3),
        ]);

        expect($user->getLastUpdatedText())->toBe('3 days ago');
    });
});
