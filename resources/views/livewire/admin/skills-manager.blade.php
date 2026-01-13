<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6">
        <flux:heading size="xl" level="1">Manage Skills</flux:heading>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="skills">Skills</flux:tab>
            <flux:tab name="categories">Categories</flux:tab>
        </flux:tabs>

        {{-- Skills Tab --}}
        <flux:tab.panel name="skills" class="pt-6">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-end justify-between mb-6">
                <div class="w-full sm:max-w-sm">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search skills..."
                        icon="magnifying-glass"
                    />
                </div>
                <flux:button wire:click="openCreateModal" icon="plus">
                    Add Skill
                </flux:button>
            </div>

            @if ($this->skills->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column class="hidden md:table-cell">Description</flux:table.column>
                        <flux:table.column class="hidden sm:table-cell">Category</flux:table.column>
                        <flux:table.column>Users</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->skills as $skill)
                            <flux:table.row wire:key="skill-{{ $skill->id }}">
                                <flux:table.cell class="font-medium">
                                    @if ($skill->isPending())
                                        <flux:badge as="button" wire:click="approveSkill({{ $skill->id }})" size="sm" color="amber" class="mr-2 cursor-pointer" title="Click to approve this skill">Pending</flux:badge>
                                    @endif
                                    {{ $skill->name }}
                                </flux:table.cell>
                                <flux:table.cell class="hidden md:table-cell max-w-xs truncate">{{ $skill->description }}</flux:table.cell>
                                <flux:table.cell class="hidden sm:table-cell">{{ $skill->category?->name ?? '-' }}</flux:table.cell>
                                <flux:table.cell data-test="skill-{{ $skill->id }}-users-count">@if ($skill->isPending()){{ $skill->users->first()?->short_name ?? '-' }}@else{{ $skill->users_count }}@endif</flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                        <flux:menu>
                                            @if ($skill->isPending())
                                                <flux:menu.item icon="check" wire:click="approveSkill({{ $skill->id }})">
                                                    Approve
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                            @endif
                                            <flux:menu.item icon="pencil" wire:click="openEditModal({{ $skill->id }})">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete({{ $skill->id }})">
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
                    <flux:icon name="rectangle-stack" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No skills found</flux:heading>
                    <flux:text>
                        @if ($search)
                            No skills match your search. Try a different term.
                        @else
                            No skills have been created yet. Add one to get started.
                        @endif
                    </flux:text>
                </div>
            @endif
        </flux:tab.panel>

        {{-- Categories Tab --}}
        <flux:tab.panel name="categories" class="pt-6">
            <div class="flex justify-end mb-6">
                <flux:button wire:click="openCreateCategoryModal" icon="plus">
                    Add Category
                </flux:button>
            </div>

            @if ($this->categories->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Colour</flux:table.column>
                        <flux:table.column>Skills</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->categories as $category)
                            <flux:table.row wire:key="category-{{ $category->id }}">
                                <flux:table.cell class="font-medium">{{ $category->name }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($category->colour)
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded {{ $category->colour->bgClass() }}"></span>
                                            <span>{{ $category->colour->label() }}</span>
                                        </div>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell data-test="category-{{ $category->id }}-skills-count">{{ $category->skills_count }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil" wire:click="openEditCategoryModal({{ $category->id }})">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item variant="danger" icon="trash" wire:click="confirmDeleteCategory({{ $category->id }})">
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
                    <flux:icon name="folder" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
                    <flux:heading size="lg" class="mb-2">No categories</flux:heading>
                    <flux:text>
                        No skill categories have been created yet. Add one to organise your skills.
                    </flux:text>
                </div>
            @endif
        </flux:tab.panel>
    </flux:tab.group>

    {{-- Create/Edit Skill Modal --}}
    <flux:modal wire:model="showSkillModal" variant="flyout">
        <form wire:submit="saveSkill">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingSkillId ? 'Edit Skill' : 'Add New Skill' }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ $editingSkillId ? 'Update the skill details.' : 'Add a new skill to the system. It will be immediately available to all users.' }}
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Skill Name</flux:label>
                        <flux:input wire:model="skillName" placeholder="e.g., Kubernetes" />
                        <flux:error name="skillName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="skillDescription" placeholder="Brief description of the skill..." rows="3" />
                        <flux:error name="skillDescription" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="skillCategoryId" variant="combobox" placeholder="Select or create category..." :filter="false" clearable>
                            <x-slot name="input">
                                <flux:select.input wire:model.live="categorySearchTerm" placeholder="Search or create..." />
                            </x-slot>

                            @foreach ($this->filteredCategoryOptions['categories'] as $category)
                                <flux:select.option value="{{ $category->id }}" wire:key="cat-{{ $category->id }}">{{ $category->name }}</flux:select.option>
                            @endforeach

                            <flux:select.option.create wire:click="createCategoryFromSearch" min-length="2">
                                Create "<span wire:text="categorySearchTerm"></span>"
                            </flux:select.option.create>
                        </flux:select>
                        <flux:error name="skillCategoryId" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeSkillModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingSkillId ? 'Update Skill' : 'Create Skill' }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Skill Confirmation Modal --}}
    <flux:modal wire:model="deletingSkillId" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Skill</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to delete this skill? This will also remove it from all users who have it assigned. This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteSkill">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Create/Edit Category Modal --}}
    <flux:modal wire:model="showCategoryModal" variant="flyout">
        <form wire:submit="saveCategory">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingCategoryId ? 'Edit Category' : 'Add New Category' }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ $editingCategoryId ? 'Update the category details.' : 'Add a new category to organise skills.' }}
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Category Name</flux:label>
                        <flux:input wire:model="categoryName" placeholder="e.g., Programming Languages" />
                        <flux:error name="categoryName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Colour</flux:label>
                        <flux:select wire:model="categoryColour">
                            <flux:select.option value="">No colour</flux:select.option>
                            @foreach ($this->fluxColours as $colour)
                                <flux:select.option value="{{ $colour->value }}">{{ $colour->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="categoryColour" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeCategoryModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingCategoryId ? 'Update Category' : 'Create Category' }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Category Modal --}}
    <flux:modal wire:model="deletingCategoryId" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Category</flux:heading>
                @if ($this->deletingCategory?->skills_count > 0)
                    <flux:text class="mt-2">
                        This category has {{ $this->deletingCategory->skills_count }} skill(s). Please select a category to migrate them to before deleting.
                    </flux:text>
                @else
                    <flux:text class="mt-2">
                        Are you sure you want to delete this category? This action cannot be undone.
                    </flux:text>
                @endif
            </div>

            @if ($this->deletingCategory?->skills_count > 0)
                <flux:field>
                    <flux:label>Migrate skills to</flux:label>
                    <flux:select wire:model="migrateToCategoryId">
                        <flux:select.option value="">Select a category...</flux:select.option>
                        @foreach ($this->migrationTargetCategories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="migrateToCategoryId" />
                </flux:field>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDeleteCategory">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteCategory">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
