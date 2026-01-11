<?php

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;

it('can create a skill with a category', function () {
    $category = SkillCategory::factory()->create(['name' => 'Programming']);
    $skill = Skill::factory()->create([
        'name' => 'PHP',
        'skill_category_id' => $category->id,
    ]);

    expect($skill->category->name)->toBe('Programming');
    expect($category->skills)->toHaveCount(1);
});

it('can create an approved skill', function () {
    $admin = User::factory()->admin()->create();
    $skill = Skill::factory()->approved($admin)->create();

    expect($skill->isApproved())->toBeTrue();
    expect($skill->isPending())->toBeFalse();
    expect($skill->approvedBy->id)->toBe($admin->id);
});

it('can create a pending skill', function () {
    $skill = Skill::factory()->pending()->create();

    expect($skill->isPending())->toBeTrue();
    expect($skill->isApproved())->toBeFalse();
    expect($skill->approved_by)->toBeNull();
    expect($skill->approved_at)->toBeNull();
});

it('can scope to approved skills only', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved($admin)->count(3)->create();
    Skill::factory()->pending()->count(2)->create();

    expect(Skill::approved()->count())->toBe(3);
    expect(Skill::pending()->count())->toBe(2);
});

it('can attach skills to a user with a level', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill->id, ['level' => SkillLevel::High->value]);

    expect($user->skills)->toHaveCount(1);
    expect($user->skills->first()->pivot->level)->toBe(SkillLevel::High);
});

it('can get the skill level for a user', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill->id, ['level' => SkillLevel::Medium->value]);
    $user->load('skills');

    expect($user->getSkillLevel($skill))->toBe(SkillLevel::Medium);
});

it('updates last_updated_skills_at when touchSkillsUpdatedAt is called', function () {
    $user = User::factory()->create(['last_updated_skills_at' => null]);

    expect($user->last_updated_skills_at)->toBeNull();

    $user->touchSkillsUpdatedAt();

    expect($user->fresh()->last_updated_skills_at)->not->toBeNull();
});

it('returns null skill level for unassigned skill', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    expect($user->getSkillLevel($skill))->toBeNull();
});

describe('getTrendingSkills', function () {
    it('returns skills added recently ordered by popularity', function () {
        $admin = User::factory()->admin()->create();
        $users = User::factory()->count(5)->create();

        $popularSkill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);
        $lessPopularSkill = Skill::factory()->approved($admin)->create(['name' => 'Git']);
        $unpopularSkill = Skill::factory()->approved($admin)->create(['name' => 'COBOL']);

        // 4 users add Docker
        foreach ($users->take(4) as $user) {
            $user->skills()->attach($popularSkill, ['level' => SkillLevel::Medium->value]);
        }

        // 2 users add Git
        foreach ($users->take(2) as $user) {
            $user->skills()->attach($lessPopularSkill, ['level' => SkillLevel::Low->value]);
        }

        // No one adds COBOL

        $trending = Skill::getTrendingSkills();

        expect($trending)->toHaveCount(2);
        expect($trending->first()->name)->toBe('Docker');
        expect($trending->first()->recent_additions_count)->toBe(4);
        expect($trending->last()->name)->toBe('Git');
        expect($trending->last()->recent_additions_count)->toBe(2);
    });

    it('excludes skills added outside the time window', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $recentSkill = Skill::factory()->approved($admin)->create(['name' => 'Recent']);
        $oldSkill = Skill::factory()->approved($admin)->create(['name' => 'Old']);

        // Add recent skill now
        $user->skills()->attach($recentSkill, ['level' => SkillLevel::High->value]);

        // Add old skill 60 days ago (outside default 30-day window)
        $user->skills()->attach($oldSkill, [
            'level' => SkillLevel::High->value,
            'created_at' => now()->subDays(60),
        ]);

        $trending = Skill::getTrendingSkills(days: 30);

        expect($trending)->toHaveCount(1);
        expect($trending->first()->name)->toBe('Recent');
    });

    it('respects the limit parameter', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $skills = Skill::factory()->approved($admin)->count(10)->create();
        foreach ($skills as $skill) {
            $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        }

        $trending = Skill::getTrendingSkills(limit: 3);

        expect($trending)->toHaveCount(3);
    });

    it('excludes pending skills', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $approvedSkill = Skill::factory()->approved($admin)->create();
        $pendingSkill = Skill::factory()->pending()->create();

        $user->skills()->attach($approvedSkill, ['level' => SkillLevel::High->value]);
        $user->skills()->attach($pendingSkill, ['level' => SkillLevel::High->value]);

        $trending = Skill::getTrendingSkills();

        expect($trending)->toHaveCount(1);
        expect($trending->first()->id)->toBe($approvedSkill->id);
    });

    it('returns empty collection when no skills added recently', function () {
        $admin = User::factory()->admin()->create();
        Skill::factory()->approved($admin)->count(3)->create();

        $trending = Skill::getTrendingSkills();

        expect($trending)->toBeEmpty();
    });
});
