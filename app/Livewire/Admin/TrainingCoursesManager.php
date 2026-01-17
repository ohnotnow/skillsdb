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

    // Course Create/Edit modal state
    public bool $showCourseModal = false;

    public ?int $editingCourseId = null;

    public string $courseName = '';

    public string $courseDescription = '';

    public string $coursePrerequisites = '';

    public $courseCost = '';

    public bool $courseOffersCertification = false;

    public $courseSupplier = '';

    public string $supplierSearchTerm = '';

    public string $skillSearchTerm = '';

    /** @var array<int> */
    public array $courseSkillIds = [];

    // Course Delete confirmation
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
    public function suppliers()
    {
        return TrainingSupplier::orderBy('name')->get();
    }

    #[Computed]
    public function skills()
    {
        return Skill::approved()->orderBy('name')->get();
    }

    #[Computed]
    public function filteredSupplierOptions()
    {
        $search = trim($this->supplierSearchTerm);

        $suppliers = TrainingSupplier::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return $suppliers;
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
        $this->reset([
            'editingCourseId',
            'courseName',
            'courseDescription',
            'coursePrerequisites',
            'courseCost',
            'courseOffersCertification',
            'courseSupplier',
            'supplierSearchTerm',
            'skillSearchTerm',
            'courseSkillIds',
        ]);
        $this->showCourseModal = true;
    }

    public function openEditModal(int $courseId): void
    {
        $course = TrainingCourse::with('skills')->findOrFail($courseId);
        $this->editingCourseId = $course->id;
        $this->courseName = $course->name;
        $this->courseDescription = $course->description ?? '';
        $this->coursePrerequisites = $course->prerequisites ?? '';
        $this->courseCost = $course->cost ?? '';
        $this->courseOffersCertification = $course->offers_certification;
        $this->courseSupplier = $course->training_supplier_id ?? '';
        $this->supplierSearchTerm = '';
        $this->skillSearchTerm = '';
        $this->courseSkillIds = $course->skills->pluck('id')->toArray();
        $this->showCourseModal = true;
    }

    public function closeCourseModal(): void
    {
        $this->showCourseModal = false;
    }

    public function createSupplierInline(): void
    {
        $name = trim($this->supplierSearchTerm);

        if (! $name) {
            return;
        }

        $supplier = TrainingSupplier::create(['name' => $name]);

        $this->courseSupplier = $supplier->id;
        $this->supplierSearchTerm = '';
        unset($this->suppliers, $this->filteredSupplierOptions);

        Flux::toast(heading: 'Supplier created.', text: '', variant: 'success');
    }

    public function saveCourse(): void
    {
        $this->validate([
            'courseName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('training_courses', 'name')->ignore($this->editingCourseId),
            ],
            'courseDescription' => ['nullable', 'string', 'max:5000'],
            'coursePrerequisites' => ['nullable', 'string', 'max:2000'],
            'courseCost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'courseOffersCertification' => ['boolean'],
            'courseSupplier' => ['nullable', 'exists:training_suppliers,id'],
            'courseSkillIds' => ['array'],
            'courseSkillIds.*' => ['exists:skills,id'],
        ]);

        $data = [
            'name' => $this->courseName,
            'description' => $this->courseDescription ?: null,
            'prerequisites' => $this->coursePrerequisites ?: null,
            'cost' => $this->courseCost !== '' ? $this->courseCost : null,
            'offers_certification' => $this->courseOffersCertification,
            'training_supplier_id' => $this->courseSupplier ?: null,
        ];

        if ($this->editingCourseId) {
            $course = TrainingCourse::findOrFail($this->editingCourseId);
            $course->update($data);
            $message = 'Course updated.';
        } else {
            $course = TrainingCourse::create($data);
            $message = 'Course created.';
        }

        $course->skills()->sync($this->courseSkillIds);

        $this->closeCourseModal();
        unset($this->courses);

        Flux::toast(heading: $message, text: '', variant: 'success');
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

        $course = TrainingCourse::findOrFail($this->deletingCourseId);
        $course->delete();

        $this->deletingCourseId = null;
        unset($this->courses);

        Flux::toast(heading: 'Course deleted.', text: '', variant: 'success');
    }

    public function render()
    {
        return view('livewire.admin.training-courses-manager');
    }
}
