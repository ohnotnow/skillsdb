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
    expect($user->skills->first()->pivot->level)->toBe(SkillLevel::High->value);
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
