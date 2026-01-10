<?php

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;

it('requires authentication', function () {
    $this->getJson('/api/users')
        ->assertUnauthorized();
});

it('returns users with their skills', function () {
    $user = User::factory()->create();
    $category = SkillCategory::factory()->create(['name' => 'DevOps']);
    $skill = Skill::factory()->approved($user)->create([
        'name' => 'Docker',
        'skill_category_id' => $category->id,
    ]);

    $user->skills()->attach($skill->id, ['level' => SkillLevel::High->value]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $user->id)
        ->assertJsonPath('data.0.username', $user->username)
        ->assertJsonPath('data.0.full_name', $user->full_name)
        ->assertJsonPath('data.0.email', $user->email)
        ->assertJsonPath('data.0.skills.0.name', 'Docker')
        ->assertJsonPath('data.0.skills.0.category', 'DevOps')
        ->assertJsonPath('data.0.skills.0.level', 'High')
        ->assertJsonPath('data.0.skills.0.level_value', 3);
});

it('returns all users', function () {
    $users = User::factory()->count(3)->create();

    $response = $this->actingAs($users->first(), 'sanctum')
        ->getJson('/api/users');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('does not include password or remember_token in response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users');

    $response->assertOk()
        ->assertJsonMissingPath('data.0.password')
        ->assertJsonMissingPath('data.0.remember_token');
});

it('includes last_updated_skills_at timestamp', function () {
    $user = User::factory()->create([
        'last_updated_skills_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users');

    $response->assertOk()
        ->assertJsonPath('data.0.last_updated_skills_at', $user->last_updated_skills_at->toISOString());
});

it('returns empty skills array for users without skills', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users');

    $response->assertOk()
        ->assertJsonPath('data.0.skills', []);
});
