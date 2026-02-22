<?php

namespace App\Livewire\Admin;

use App\Enums\FluxColour;
use App\Models\Skill;
use App\Models\SkillCategory;
use Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property \Illuminate\Database\Eloquent\Collection $skills
 * @property \Illuminate\Database\Eloquent\Collection $categories
 * @property array $fluxColours
 * @property array $filteredCategoryOptions
 * @property \Illuminate\Support\Collection $filteredParentSkillOptions
 * @property \App\Models\SkillCategory|null $deletingCategory
 * @property \Illuminate\Database\Eloquent\Collection $migrationTargetCategories
 */
#[Layout('components.layouts.app')]
class SkillsManager extends Component
{
    #[Url(except: 'skills')]
    public string $tab = 'skills';

    #[Url]
    public $search = '';

    // Skill Create/Edit modal state
    public bool $showSkillModal = false;

    public ?int $editingSkillId = null;

    public string $skillName = '';

    public string $skillDescription = '';

    public $skillCategoryId = '';

    public bool $skillIsReportable = false;

    public $skillParentId = '';

    public string $categorySearchTerm = '';

    public string $parentSkillSearchTerm = '';

    // Skill Delete confirmation
    public ?int $deletingSkillId = null;

    // Category Create/Edit modal state
    public bool $showCategoryModal = false;

    public ?int $editingCategoryId = null;

    public string $categoryName = '';

    public $categoryColour = '';

    // Category Delete modal state
    public ?int $deletingCategoryId = null;

    public $migrateToCategoryId = '';

    #[Computed]
    public function skills()
    {
        return Skill::query()
            ->with(['category', 'parent', 'users'])
            ->withCount('users')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('parent', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByRaw('approved_at IS NOT NULL, approved_at DESC')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return SkillCategory::withCount('skills')->orderBy('name')->get();
    }

    #[Computed]
    public function fluxColours(): array
    {
        return FluxColour::cases();
    }

    #[Computed]
    public function filteredCategoryOptions()
    {
        $search = trim($this->categorySearchTerm);

        $categories = SkillCategory::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $exactMatch = $search && $categories->contains(fn ($c) => strtolower($c->name) === strtolower($search));

        return [
            'categories' => $categories,
            'showCreate' => $search && ! $exactMatch,
            'createName' => $search,
        ];
    }

    #[Computed]
    public function filteredParentSkillOptions()
    {
        if (! $this->showSkillModal) {
            return collect();
        }

        $search = trim($this->parentSkillSearchTerm);

        return Skill::query()
            ->approved()
            ->when($this->editingSkillId, fn ($q) => $q->where('id', '!=', $this->editingSkillId))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingSkillId', 'skillName', 'skillDescription', 'skillCategoryId', 'skillParentId', 'skillIsReportable', 'categorySearchTerm', 'parentSkillSearchTerm']);
        $this->showSkillModal = true;
    }

    public function openEditModal(int $skillId): void
    {
        $skill = Skill::findOrFail($skillId);
        $this->editingSkillId = $skill->id;
        $this->skillName = $skill->name;
        $this->skillDescription = $skill->description ?? '';
        $this->skillCategoryId = $skill->skill_category_id ?? '';
        $this->skillParentId = $skill->parent_id ?? '';
        $this->skillIsReportable = $skill->is_reportable;
        $this->categorySearchTerm = '';
        $this->parentSkillSearchTerm = '';
        $this->showSkillModal = true;
    }

    public function closeSkillModal(): void
    {
        $this->showSkillModal = false;
    }

    public function createCategoryFromSearch(): void
    {
        $category = SkillCategory::firstOrCreate(['name' => $this->categorySearchTerm]);
        $this->skillCategoryId = $category->id;
        $this->categorySearchTerm = '';
        unset($this->categories, $this->filteredCategoryOptions);
    }

    public function saveSkill(): void
    {
        $parentIdRules = ['nullable', 'exists:skills,id'];
        if ($this->editingSkillId && $this->skillParentId) {
            $parentIdRules[] = Rule::notIn([$this->editingSkillId]);
        }

        $this->validate([
            'skillName' => ['required', 'string', 'max:255', 'unique:skills,name'.($this->editingSkillId ? ','.$this->editingSkillId : '')],
            'skillDescription' => ['nullable', 'string', 'max:1000'],
            'skillCategoryId' => ['nullable', 'exists:skill_categories,id'],
            'skillParentId' => $parentIdRules,
        ]);

        $data = [
            'name' => $this->skillName,
            'description' => $this->skillDescription ?: null,
            'skill_category_id' => $this->skillCategoryId ?: null,
            'parent_id' => $this->skillParentId ?: null,
            'is_reportable' => $this->skillIsReportable,
        ];

        if ($this->editingSkillId) {
            $skill = Skill::findOrFail($this->editingSkillId);
            $skill->update($data);
            $message = 'Skill updated.';
        } else {
            // Admin-created skills are auto-approved
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
            Skill::create($data);
            $message = 'Skill created.';
        }

        $this->closeSkillModal();
        unset($this->skills);

        Flux::toast(heading: $message, text: '', variant: 'success');
    }

    public function confirmDelete(int $skillId): void
    {
        $this->deletingSkillId = $skillId;
    }

    public function cancelDelete(): void
    {
        $this->deletingSkillId = null;
    }

    public function deleteSkill(): void
    {
        if (! $this->deletingSkillId) {
            return;
        }

        $skill = Skill::findOrFail($this->deletingSkillId);
        $skill->delete();

        $this->deletingSkillId = null;
        unset($this->skills);

        Flux::toast(heading: 'Skill deleted.', text: '', variant: 'success');
    }

    public function approveSkill(int $skillId): void
    {
        $skill = Skill::findOrFail($skillId);

        if ($skill->isApproved()) {
            return;
        }

        $skill->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        unset($this->skills);

        Flux::toast(variant: 'success', heading: 'Skill approved.', text: "'{$skill->name}' is now visible to all users.");
    }

    // Category CRUD methods

    public function openCreateCategoryModal(): void
    {
        $this->reset(['editingCategoryId', 'categoryName', 'categoryColour']);
        $this->showCategoryModal = true;
    }

    public function openEditCategoryModal(int $categoryId): void
    {
        $category = SkillCategory::findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryColour = $category->colour?->value ?? '';
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('skill_categories', 'name')->ignore($this->editingCategoryId),
            ],
            'categoryColour' => ['nullable', Rule::enum(FluxColour::class)],
        ]);

        $data = [
            'name' => $this->categoryName,
            'colour' => $this->categoryColour ?: null,
        ];

        if ($this->editingCategoryId) {
            $category = SkillCategory::findOrFail($this->editingCategoryId);
            $category->update($data);
            $message = 'Category updated.';
        } else {
            SkillCategory::create($data);
            $message = 'Category created.';
        }

        $this->closeCategoryModal();
        unset($this->categories);

        Flux::toast(text: '', heading: $message, variant: 'success');
    }

    public function confirmDeleteCategory(int $categoryId): void
    {
        $this->deletingCategoryId = $categoryId;
        $this->migrateToCategoryId = '';
    }

    public function cancelDeleteCategory(): void
    {
        $this->deletingCategoryId = null;
        $this->migrateToCategoryId = '';
    }

    #[Computed]
    public function deletingCategory(): ?SkillCategory
    {
        if (! $this->deletingCategoryId) {
            return null;
        }

        return SkillCategory::withCount('skills')->find($this->deletingCategoryId);
    }

    #[Computed]
    public function migrationTargetCategories()
    {
        if (! $this->deletingCategoryId) {
            return collect();
        }

        return SkillCategory::where('id', '!=', $this->deletingCategoryId)
            ->orderBy('name')
            ->get();
    }

    public function deleteCategory(): void
    {
        if (! $this->deletingCategoryId) {
            return;
        }

        $category = SkillCategory::withCount('skills')->findOrFail($this->deletingCategoryId);

        if ($category->skills_count > 0) {
            $this->validate([
                'migrateToCategoryId' => ['required', 'exists:skill_categories,id'],
            ], [
                'migrateToCategoryId.required' => 'Please select a category to migrate skills to.',
            ]);

            Skill::where('skill_category_id', $category->id)
                ->update(['skill_category_id' => $this->migrateToCategoryId]);
        }

        $category->delete();

        $this->deletingCategoryId = null;
        $this->migrateToCategoryId = '';
        unset($this->categories, $this->skills, $this->deletingCategory, $this->migrationTargetCategories);

        Flux::toast(text: '', heading: 'Category deleted.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.skills-manager');
    }
}
