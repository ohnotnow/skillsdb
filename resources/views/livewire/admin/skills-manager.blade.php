<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6">
        <flux:heading size="xl" level="1">Manage Skills</flux:heading>
        <flux:button wire:click="openCreateModal" icon="plus">
            Add Skill
        </flux:button>
    </div>

    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center mb-6">
        <div class="flex-1 w-full">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search skills by name, description, or category..."
                icon="magnifying-glass"
            />
        </div>
        <flux:field variant="inline">
            <flux:label>
                Show pending only
                @if ($this->pendingCount > 0)
                    <flux:badge size="sm" color="amber" class="ml-1">{{ $this->pendingCount }}</flux:badge>
                @endif
            </flux:label>
            <flux:switch wire:model.live="showPendingOnly" />
        </flux:field>
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
                                <flux:badge size="sm" color="amber" class="mr-2">Pending</flux:badge>
                            @endif
                            {{ $skill->name }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell max-w-xs truncate">{{ $skill->description }}</flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">{{ $skill->category?->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($skill->isPending())
                                {{ $skill->users->first()?->short_name ?? '-' }}
                            @else
                                {{ $skill->users_count }}
                            @endif
                        </flux:table.cell>
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
                @if ($search || $showPendingOnly)
                    No skills match your current filters. Try adjusting your search or clearing filters.
                @else
                    No skills have been created yet. Add one to get started.
                @endif
            </flux:text>
        </div>
    @endif

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
                        <flux:select wire:model="skillCategoryId">
                            <flux:select.option value="">No category</flux:select.option>
                            @foreach ($this->categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                            @endforeach
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

    {{-- Delete Confirmation Modal --}}
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
</div>
