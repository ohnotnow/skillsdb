<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\SkillCategory;
use Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsManager extends Component
{
    #[Url]
    public $search = '';

    // Create/Edit modal state
    public bool $showSkillModal = false;

    public ?int $editingSkillId = null;

    public string $skillName = '';

    public string $skillDescription = '';

    public $skillCategoryId = '';

    // Delete confirmation
    public ?int $deletingSkillId = null;

    #[Computed]
    public function skills()
    {
        return Skill::query()
            ->with(['category', 'users'])
            ->withCount('users')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByRaw('approved_at IS NOT NULL, approved_at DESC')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return SkillCategory::orderBy('name')->get();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingSkillId', 'skillName', 'skillDescription', 'skillCategoryId']);
        $this->showSkillModal = true;
    }

    public function openEditModal(int $skillId): void
    {
        $skill = Skill::findOrFail($skillId);
        $this->editingSkillId = $skill->id;
        $this->skillName = $skill->name;
        $this->skillDescription = $skill->description ?? '';
        $this->skillCategoryId = $skill->skill_category_id ?? '';
        $this->showSkillModal = true;
    }

    public function closeSkillModal(): void
    {
        $this->showSkillModal = false;
    }

    public function saveSkill(): void
    {
        $this->validate([
            'skillName' => ['required', 'string', 'max:255', 'unique:skills,name'.($this->editingSkillId ? ','.$this->editingSkillId : '')],
            'skillDescription' => ['nullable', 'string', 'max:1000'],
            'skillCategoryId' => ['nullable', 'exists:skill_categories,id'],
        ]);

        $data = [
            'name' => $this->skillName,
            'description' => $this->skillDescription ?: null,
            'skill_category_id' => $this->skillCategoryId ?: null,
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

        $skill->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        unset($this->skills);

        Flux::toast(variant: 'success', heading: 'Skill approved.', text: "'{$skill->name}' is now visible to all users.");
    }

    public function render()
    {
        return view('livewire.admin.skills-manager');
    }
}
