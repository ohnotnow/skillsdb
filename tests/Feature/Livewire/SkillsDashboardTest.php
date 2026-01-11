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
