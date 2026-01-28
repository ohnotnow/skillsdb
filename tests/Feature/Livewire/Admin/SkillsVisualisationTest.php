<?php

use App\Enums\FluxColour;
use App\Livewire\Admin\SkillsVisualisation;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get('/admin/skills/visualisation')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/skills/visualisation')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/skills/visualisation')
        ->assertSuccessful()
        ->assertSeeLivewire(SkillsVisualisation::class);
});

it('displays the page heading', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/skills/visualisation')
        ->assertSee('Skills Map');
});

it('returns hierarchy data with root node', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;

    expect($data)->toHaveKey('name', 'Skills');
    expect($data)->toHaveKey('type', 'root');
    expect($data)->toHaveKey('children');
});

it('groups skills by category', function () {
    $admin = User::factory()->admin()->create();
    $category = SkillCategory::factory()->create(['name' => 'Development', 'colour' => FluxColour::Blue]);
    Skill::factory()->approved()->create(['name' => 'PHP', 'skill_category_id' => $category->id]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $categoryNode = collect($data['children'])->firstWhere('name', 'Development');

    expect($categoryNode)->not->toBeNull();
    expect($categoryNode['type'])->toBe('category');
    expect($categoryNode['colour'])->toBe('blue');
    expect($categoryNode['children'])->toHaveCount(1);
    expect($categoryNode['children'][0]['name'])->toBe('PHP');
});

it('puts uncategorised skills in an Uncategorised group', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'Miscellaneous', 'skill_category_id' => null]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $uncategorised = collect($data['children'])->firstWhere('name', 'Uncategorised');

    expect($uncategorised)->not->toBeNull();
    expect($uncategorised['colour'])->toBe('zinc');
    expect($uncategorised['children'][0]['name'])->toBe('Miscellaneous');
});

it('includes user count for skills', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(3)->create();
    $skill = Skill::factory()->approved()->create(['name' => 'Docker']);

    foreach ($users as $user) {
        $user->skills()->attach($skill->id, ['level' => 1]);
    }

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $uncategorised = collect($data['children'])->firstWhere('name', 'Uncategorised');
    $skillNode = $uncategorised['children'][0];

    expect($skillNode['userCount'])->toBe(3);
});

it('nests child skills under parent skills', function () {
    $admin = User::factory()->admin()->create();
    $category = SkillCategory::factory()->create(['name' => 'Development']);
    $parent = Skill::factory()->approved()->create(['name' => 'PHP', 'skill_category_id' => $category->id]);
    Skill::factory()->approved()->create(['name' => 'Laravel', 'skill_category_id' => $category->id, 'parent_id' => $parent->id]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $categoryNode = collect($data['children'])->firstWhere('name', 'Development');
    $phpNode = $categoryNode['children'][0];

    expect($phpNode['name'])->toBe('PHP');
    expect($phpNode['children'])->toHaveCount(1);
    expect($phpNode['children'][0]['name'])->toBe('Laravel');
});

it('handles deeply nested skills', function () {
    $admin = User::factory()->admin()->create();
    $category = SkillCategory::factory()->create(['name' => 'Development']);
    $php = Skill::factory()->approved()->create(['name' => 'PHP', 'skill_category_id' => $category->id]);
    $laravel = Skill::factory()->approved()->create(['name' => 'Laravel', 'skill_category_id' => $category->id, 'parent_id' => $php->id]);
    Skill::factory()->approved()->create(['name' => 'Livewire', 'skill_category_id' => $category->id, 'parent_id' => $laravel->id]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $categoryNode = collect($data['children'])->firstWhere('name', 'Development');
    $phpNode = $categoryNode['children'][0];
    $laravelNode = $phpNode['children'][0];

    expect($laravelNode['name'])->toBe('Laravel');
    expect($laravelNode['children'])->toHaveCount(1);
    expect($laravelNode['children'][0]['name'])->toBe('Livewire');
});

it('excludes pending skills from hierarchy', function () {
    $admin = User::factory()->admin()->create();
    Skill::factory()->approved()->create(['name' => 'Approved Skill']);
    Skill::factory()->pending()->create(['name' => 'Pending Skill']);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $allSkillNames = collect($data['children'])
        ->flatMap(fn ($cat) => collect($cat['children'])->pluck('name'))
        ->all();

    expect($allSkillNames)->toContain('Approved Skill');
    expect($allSkillNames)->not->toContain('Pending Skill');
});

it('includes total user count on categories', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(3)->create();
    $category = SkillCategory::factory()->create(['name' => 'Development']);
    $skill1 = Skill::factory()->approved()->create(['name' => 'PHP', 'skill_category_id' => $category->id]);
    $skill2 = Skill::factory()->approved()->create(['name' => 'Python', 'skill_category_id' => $category->id]);

    // 3 users have PHP, 2 users have Python = 5 total
    foreach ($users as $user) {
        $user->skills()->attach($skill1->id, ['level' => 1]);
    }
    $users[0]->skills()->attach($skill2->id, ['level' => 1]);
    $users[1]->skills()->attach($skill2->id, ['level' => 1]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $categoryNode = collect($data['children'])->firstWhere('name', 'Development');

    expect($categoryNode['userCount'])->toBe(5);
});

it('includes nested skill user counts in category total', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(2)->create();
    $category = SkillCategory::factory()->create(['name' => 'Development']);
    $parent = Skill::factory()->approved()->create(['name' => 'PHP', 'skill_category_id' => $category->id]);
    $child = Skill::factory()->approved()->create(['name' => 'Laravel', 'skill_category_id' => $category->id, 'parent_id' => $parent->id]);

    // 2 users have PHP, 1 user has Laravel = 3 total
    foreach ($users as $user) {
        $user->skills()->attach($parent->id, ['level' => 1]);
    }
    $users[0]->skills()->attach($child->id, ['level' => 1]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $categoryNode = collect($data['children'])->firstWhere('name', 'Development');

    expect($categoryNode['userCount'])->toBe(3);
});

it('excludes empty categories from hierarchy', function () {
    $admin = User::factory()->admin()->create();
    SkillCategory::factory()->create(['name' => 'Empty Category']);
    $populatedCategory = SkillCategory::factory()->create(['name' => 'Has Skills']);
    Skill::factory()->approved()->create(['skill_category_id' => $populatedCategory->id]);

    $component = Livewire::actingAs($admin)->test(SkillsVisualisation::class);

    $data = $component->instance()->hierarchyData;
    $categoryNames = collect($data['children'])->pluck('name')->all();

    expect($categoryNames)->toContain('Has Skills');
    expect($categoryNames)->not->toContain('Empty Category');
});
