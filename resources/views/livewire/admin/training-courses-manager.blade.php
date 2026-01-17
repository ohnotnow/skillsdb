<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6">
        <flux:heading size="xl" level="1">Training Courses</flux:heading>
    </div>

    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-end justify-between mb-6">
        <div class="w-full sm:max-w-sm">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search courses..."
                icon="magnifying-glass"
            />
        </div>
        <flux:button wire:click="openCreateModal" icon="plus">
            Add Course
        </flux:button>
    </div>

    @if ($this->courses->count() > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Supplier</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Cost</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">Certification</flux:table.column>
                <flux:table.column>Skills</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Enrollments</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->courses as $course)
                    <flux:table.row wire:key="course-{{ $course->id }}">
                        <flux:table.cell class="font-medium">
                            {{ $course->name }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ $course->supplier?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($course->isFree())
                                <flux:badge color="green" size="sm">Free</flux:badge>
                            @else
                                {{ number_format($course->cost, 2) }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden lg:table-cell">
                            @if ($course->offers_certification)
                                <flux:badge color="sky" size="sm">Yes</flux:badge>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell data-test="course-{{ $course->id }}-skills-count">
                            {{ $course->skills_count }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell" data-test="course-{{ $course->id }}-users-count">
                            {{ $course->users_count }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="openEditModal({{ $course->id }})">
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete({{ $course->id }})">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="text-center py-12">
            <flux:icon name="academic-cap" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No training courses found</flux:heading>
            <flux:text>
                @if ($search)
                    No courses match your search. Try a different term.
                @else
                    No training courses have been created yet. Add one to get started.
                @endif
            </flux:text>
        </div>
    @endif

    {{-- Create/Edit Course Modal --}}
    <flux:modal wire:model="showCourseModal" variant="flyout">
        <form wire:submit="saveCourse">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingCourseId ? 'Edit Course' : 'Add New Course' }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ $editingCourseId ? 'Update the course details.' : 'Add a new training course to the system.' }}
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Course Name</flux:label>
                        <flux:input wire:model="courseName" placeholder="e.g., AWS Solutions Architect" />
                        <flux:error name="courseName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="courseDescription" placeholder="Brief description of the course..." rows="3" />
                        <flux:error name="courseDescription" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Prerequisites</flux:label>
                        <flux:textarea wire:model="coursePrerequisites" placeholder="Any prior knowledge or skills required..." rows="2" />
                        <flux:error name="coursePrerequisites" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Cost</flux:label>
                            <flux:input wire:model="courseCost" type="number" step="0.01" min="0" placeholder="0.00" />
                            <flux:description>Leave blank for free courses</flux:description>
                            <flux:error name="courseCost" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Certification</flux:label>
                            <flux:switch wire:model="courseOffersCertification" label="Offers certification" />
                            <flux:error name="courseOffersCertification" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Supplier</flux:label>
                        <flux:select wire:model="courseSupplier" variant="combobox" placeholder="Select or create supplier..." :filter="false" clearable>
                            <x-slot name="input">
                                <flux:select.input wire:model.live="supplierSearchTerm" placeholder="Search or create..." />
                            </x-slot>

                            @foreach ($this->filteredSupplierOptions['suppliers'] as $supplier)
                                <flux:select.option value="{{ $supplier->id }}" wire:key="supplier-{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                            @endforeach

                            <flux:select.option.create wire:click="openQuickSupplierModal" min-length="2">
                                Create "<span wire:text="supplierSearchTerm"></span>"
                            </flux:select.option.create>
                        </flux:select>
                        <flux:error name="courseSupplier" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Related Skills</flux:label>
                        <flux:select wire:model="courseSkillIds" variant="listbox" multiple placeholder="Select skills...">
                            @foreach ($this->skills as $skill)
                                <flux:select.option value="{{ $skill->id }}" wire:key="skill-{{ $skill->id }}">{{ $skill->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:description>Skills that this course helps develop</flux:description>
                        <flux:error name="courseSkillIds" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeCourseModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingCourseId ? 'Update Course' : 'Create Course' }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Course Confirmation Modal --}}
    <flux:modal wire:model="deletingCourseId" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Course</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to delete this course? This will also remove all enrollment records. This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteCourse">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Quick Supplier Modal --}}
    <flux:modal wire:model="showQuickSupplierModal" class="max-w-md">
        <form wire:submit="saveQuickSupplier">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create Supplier</flux:heading>
                    <flux:text class="mt-2">
                        Quickly add a new training supplier.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Supplier Name</flux:label>
                        <flux:input wire:model="quickSupplierName" placeholder="e.g., Pluralsight" />
                        <flux:error name="quickSupplierName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Website</flux:label>
                        <flux:input wire:model="quickSupplierWebsite" type="url" placeholder="https://..." />
                        <flux:error name="quickSupplierWebsite" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeQuickSupplierModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Create Supplier</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
