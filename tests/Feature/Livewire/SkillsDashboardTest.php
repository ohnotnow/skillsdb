<?php

use App\Enums\SkillLevel;
use App\Livewire\SkillsDashboard;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

it('displays last updated text when user has updated skills', function () {
    $user = User::factory()->create([
        'last_updated_skills_at' => now()->subDays(2),
    ]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSee('Last updated')
        ->assertSee('2 days ago');
});

it('displays ages ago in italics when skills are stale', function () {
    $user = User::factory()->create([
        'last_updated_skills_at' => now()->subWeeks(5),
    ]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSeeHtml('<em>ages ago</em>');
});

it('displays never in italics when user has not updated skills', function () {
    $user = User::factory()->create([
        'last_updated_skills_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSeeHtml('<em>never</em>');
});

it('displays skill distribution badges when user has skills', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $lowSkill = Skill::factory()->approved($admin)->create();
    $mediumSkill = Skill::factory()->approved($admin)->create();
    $highSkill = Skill::factory()->approved($admin)->create();

    $user->skills()->attach($lowSkill, ['level' => SkillLevel::Low->value]);
    $user->skills()->attach($mediumSkill, ['level' => SkillLevel::Medium->value]);
    $user->skills()->attach($highSkill, ['level' => SkillLevel::High->value]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSee('Your skills:')
        ->assertSee('1 Low')
        ->assertSee('1 Medium')
        ->assertSee('1 High');
});

it('displays no skills added yet when user has no skills', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSee('No skills added yet')
        ->assertDontSee('Your skills:');
});

it('displays trending skills when available', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);

    // Attach skill to multiple users recently
    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
    User::factory()->create()->skills()->attach($skill, ['level' => SkillLevel::High->value]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSee('Trending in the team this month:')
        ->assertSee('Docker')
        ->assertSee('+2');
});

it('does not display trending section when no skills are trending', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertDontSee('Trending in the team this month:');
});

it('renders nothing in team mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class, ['teamMode' => true])
        ->assertDontSee('Last updated')
        ->assertDontSee('Your skills:');
});

it('displays colleague insights when user shares skills with others', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['forenames' => 'Sally', 'surname' => 'Smith']);
    $admin = User::factory()->admin()->create();

    $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);

    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
    $colleague->skills()->attach($skill, ['level' => SkillLevel::High->value]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSee('Colleagues who share your skills:')
        ->assertSee('1 has Docker')
        ->assertSee('Sally Smith');
});

it('does not display colleague insights when no colleagues share skills', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);
    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertDontSee('Colleagues who share your skills:');
});

it('does not include current user in colleague count', function () {
    $user = User::factory()->create(['forenames' => 'Current', 'surname' => 'User']);
    $colleague = User::factory()->create(['forenames' => 'Other', 'surname' => 'Person']);
    $admin = User::factory()->admin()->create();

    $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);

    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
    $colleague->skills()->attach($skill, ['level' => SkillLevel::High->value]);

    Livewire::actingAs($user)
        ->test(SkillsDashboard::class)
        ->assertSee('1 has Docker')
        ->assertDontSee('Current User');
});

it('returns correct skill points over time data', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $skill1 = Skill::factory()->approved($admin)->create();
    $skill2 = Skill::factory()->approved($admin)->create();
    $skill3 = Skill::factory()->approved($admin)->create();

    // Attach skills at different times over the past 6 months
    // Low=1, Medium=2, High=3
    $user->skills()->attach($skill1->id, [
        'level' => SkillLevel::Low->value,
        'created_at' => now()->subMonths(4),
        'updated_at' => now()->subMonths(4),
    ]);
    $user->skills()->attach($skill2->id, [
        'level' => SkillLevel::Medium->value,
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subMonths(2),
    ]);
    $user->skills()->attach($skill3->id, [
        'level' => SkillLevel::High->value,
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    $skillsOverTime = $user->getSkillsOverTime();

    // Should return 6 monthly data points with month labels and points
    expect($skillsOverTime)->toHaveCount(6);

    // Each entry should have month and points keys
    expect($skillsOverTime[0])->toHaveKeys(['month', 'points']);

    // First month (5 months ago) should have 0 points
    expect($skillsOverTime[0]['points'])->toBe(0);
    // After skill1 Low was added (4 months ago) should have 1 point
    expect($skillsOverTime[1]['points'])->toBe(1);
    // Still 1 at 3 months ago
    expect($skillsOverTime[2]['points'])->toBe(1);
    // After skill2 Medium (2 months ago) should have 3 points (1+2)
    expect($skillsOverTime[3]['points'])->toBe(3);
    // Still 3 at 1 month ago
    expect($skillsOverTime[4]['points'])->toBe(3);
    // Current month with skill3 High should have 6 points (1+2+3)
    expect($skillsOverTime[5]['points'])->toBe(6);

    // Month labels should be 3-letter abbreviations
    expect($skillsOverTime[5]['month'])->toBe(now()->format('M'));
});
