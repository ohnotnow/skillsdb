<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\SkillCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsVisualization extends Component
{
    #[Computed]
    public function hierarchyData(): array
    {
        $categories = SkillCategory::with(['skills' => function ($query) {
            $query->approved()->whereNull('parent_id')->withCount('users');
        }])->get();

        $rootSkillsWithoutCategory = Skill::approved()
            ->whereNull('skill_category_id')
            ->whereNull('parent_id')
            ->withCount('users')
            ->get();

        $children = [];

        foreach ($categories as $category) {
            if ($category->skills->isEmpty()) {
                continue;
            }

            $children[] = [
                'name' => $category->name,
                'type' => 'category',
                'colour' => $category->colour?->value ?? 'zinc',
                'children' => $category->skills->map(fn ($skill) => $this->buildSkillNode($skill))->values()->all(),
            ];
        }

        if ($rootSkillsWithoutCategory->isNotEmpty()) {
            $children[] = [
                'name' => 'Uncategorized',
                'type' => 'category',
                'colour' => 'zinc',
                'children' => $rootSkillsWithoutCategory->map(fn ($skill) => $this->buildSkillNode($skill))->values()->all(),
            ];
        }

        return [
            'name' => 'Skills',
            'type' => 'root',
            'children' => $children,
        ];
    }

    private function buildSkillNode(Skill $skill): array
    {
        $children = Skill::approved()
            ->where('parent_id', $skill->id)
            ->withCount('users')
            ->get();

        $node = [
            'name' => $skill->name,
            'type' => 'skill',
            'id' => $skill->id,
            'description' => $skill->description,
            'userCount' => $skill->users_count,
            'colour' => $skill->category?->colour?->value ?? 'zinc',
        ];

        if ($children->isNotEmpty()) {
            $node['children'] = $children->map(fn ($child) => $this->buildSkillNode($child))->values()->all();
        }

        return $node;
    }

    public function render()
    {
        return view('livewire.admin.skills-visualization');
    }
}
