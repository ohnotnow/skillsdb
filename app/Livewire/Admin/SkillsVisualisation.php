<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\SkillCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsVisualisation extends Component
{
    #[Url]
    public $layout = 'radial';

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

            $skillNodes = $category->skills->map(fn ($skill) => $this->buildSkillNode($skill))->values()->all();

            $children[] = [
                'name' => $category->name,
                'type' => 'category',
                'colour' => $category->colour?->value ?? 'zinc',
                'userCount' => $this->sumUserCounts($skillNodes),
                'children' => $skillNodes,
            ];
        }

        if ($rootSkillsWithoutCategory->isNotEmpty()) {
            $skillNodes = $rootSkillsWithoutCategory->map(fn ($skill) => $this->buildSkillNode($skill))->values()->all();

            $children[] = [
                'name' => 'Uncategorized',
                'type' => 'category',
                'colour' => 'zinc',
                'userCount' => $this->sumUserCounts($skillNodes),
                'children' => $skillNodes,
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

    private function sumUserCounts(array $nodes): int
    {
        $total = 0;

        foreach ($nodes as $node) {
            $total += $node['userCount'] ?? 0;

            if (! empty($node['children'])) {
                $total += $this->sumUserCounts($node['children']);
            }
        }

        return $total;
    }

    public function render()
    {
        return view('livewire.admin.skills-visualization');
    }
}
