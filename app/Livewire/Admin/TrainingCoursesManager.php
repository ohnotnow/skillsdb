<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\TrainingCourse;
use App\Models\TrainingSupplier;
use Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TrainingCoursesManager extends Component
{
    #[Url]
    public $search = '';

    public array $editingCourse = [
        'id' => null,
        'name' => '',
        'description' => '',
        'prerequisites' => '',
        'cost' => '',
        'offers_certification' => false,
        'training_supplier_id' => '',
        'skill_ids' => [],
    ];

    public string $supplierSearchTerm = '';

    public string $skillSearchTerm = '';

    public ?int $deletingCourseId = null;

    #[Computed]
    public function courses()
    {
        return TrainingCourse::query()
            ->with(['supplier', 'skills', 'users'])
            ->withCount(['skills', 'users'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function filteredSupplierOptions()
    {
        $search = trim($this->supplierSearchTerm);

        $suppliers = TrainingSupplier::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $exactMatch = $search && $suppliers->contains(fn ($s) => strtolower($s->name) === strtolower($search));

        return [
            'suppliers' => $suppliers,
            'showCreate' => $search && ! $exactMatch,
            'createName' => $search,
        ];
    }

    #[Computed]
    public function filteredSkillOptions()
    {
        $search = trim($this->skillSearchTerm);

        return Skill::approved()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset('editingCourse', 'supplierSearchTerm', 'skillSearchTerm');

        Flux::modal('course-modal')->show();
    }

    public function openEditModal(int $courseId): void
    {
        $course = TrainingCourse::with('skills')->findOrFail($courseId);

        $this->editingCourse = $course->toArray();
        $this->editingCourse['training_supplier_id'] = (string) $course->training_supplier_id;
        $this->editingCourse['skill_ids'] = $course->skills->pluck('id')->toArray();

        $this->reset('supplierSearchTerm', 'skillSearchTerm');

        Flux::modal('course-modal')->show();
    }

    public function createSupplierInline(): void
    {
        $name = trim($this->supplierSearchTerm);

        if (! $name) {
            return;
        }

        $supplier = TrainingSupplier::create(['name' => $name]);

        $this->editingCourse['training_supplier_id'] = (string) $supplier->id;
        $this->supplierSearchTerm = '';
        unset($this->filteredSupplierOptions);

        Flux::toast('Supplier created.', variant: 'success');
    }

    public function saveCourse(): void
    {
        $this->validate([
            'editingCourse.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('training_courses', 'name')->ignore($this->editingCourse['id']),
            ],
            'editingCourse.description' => ['nullable', 'string', 'max:5000'],
            'editingCourse.prerequisites' => ['nullable', 'string', 'max:2000'],
            'editingCourse.cost' => ['nullable', 'string', 'max:255'],
            'editingCourse.offers_certification' => ['boolean'],
            'editingCourse.training_supplier_id' => ['nullable', 'exists:training_suppliers,id'],
            'editingCourse.skill_ids' => ['array'],
            'editingCourse.skill_ids.*' => ['exists:skills,id'],
        ]);

        $course = TrainingCourse::findOrNew($this->editingCourse['id']);
        $course->fill($this->editingCourse)->save();

        $course->skills()->sync($this->editingCourse['skill_ids']);

        unset($this->courses);

        Flux::modal('course-modal')->close();
        Flux::toast('Saved.', variant: 'success');
    }

    public function confirmDelete(int $courseId): void
    {
        $this->deletingCourseId = $courseId;
    }

    public function cancelDelete(): void
    {
        $this->deletingCourseId = null;
    }

    public function deleteCourse(): void
    {
        if (! $this->deletingCourseId) {
            return;
        }

        TrainingCourse::findOrFail($this->deletingCourseId)->delete();

        $this->deletingCourseId = null;
        unset($this->courses);

        Flux::toast('Course deleted.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.training-courses-manager');
    }
}
