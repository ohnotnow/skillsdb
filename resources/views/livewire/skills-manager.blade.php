<div>
    <div class="flex justify-between items-center">
        <flux:heading size="xl" level="1">Skills Management</flux:heading>
        <div class="flex items-center gap-2">
            <flux:dropdown>
                <flux:button icon="arrow-down-tray">Download</flux:button>
                <flux:menu>
                    <flux:menu.item icon="table-cells" wire:click="downloadExcel">Excel (.xlsx)</flux:menu.item>
                    <flux:menu.item icon="document-text" wire:click="downloadCsv">CSV (.csv)</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:modal.trigger wire:click="openAddSkillModal">
                <flux:button>Add New Skill</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:separator variant="subtle" class="mt-6" />

    <flux:tab.group class="mt-6">
        <flux:tabs wire:model="activeTab">
            <flux:tab name="available-skills">Available Skills</flux:tab>
            <flux:tab name="user-skills">User Skills Assignment</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="available-skills">
            <div>
                <flux:input type="text" wire:model.live="skillSearchQuery"
                    placeholder="Search skills by name, description, or category..." class="w-full" />
                <div class="overflow-x-auto">
                    <flux:table :paginate="$skills" class="mt-6">
                        <flux:table.columns>
                            <flux:table.column sortable wire:click="sort('name')">Name</flux:table.column>
                            <flux:table.column sortable wire:click="sort('description')">Description</flux:table.column>
                            <flux:table.column sortable wire:click="sort('skill_category')">Category</flux:table.column>
                            <flux:table.column>Users Count</flux:table.column>
                            <flux:table.column>Actions</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($skills as $skill)
                                <flux:table.row :key="'skill-' . $skill->id">
                                    <flux:table.cell>{{ $skill->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $skill->description }}</flux:table.cell>
                                    <flux:table.cell>{{ $skill->skill_category }}</flux:table.cell>
                                    <flux:table.cell>{{ $skill->users_count }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                                inset="top bottom" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil">
                                                    <flux:modal.trigger
                                                        wire:click="openEditSkillModal({{ $skill->id }})">
                                                        Edit Skill
                                                    </flux:modal.trigger>
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" icon="trash"
                                                    wire:click="deleteSkill({{ $skill->id }})">
                                                    Delete
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="user-skills">
            <div>
                <flux:input type="text" wire:model.live="userSearchQuery" placeholder="Search users by name..."
                    class="w-full" />

                <flux:table class="mt-6">
                    <flux:table.columns>
                        <flux:table.column>User</flux:table.column>
                        <flux:table.column>Skills</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($users as $user)
                            <flux:table.row :key="'user-' . $user->id">
                                <flux:table.cell>
                                    {{ $user->full_name }}

                                </flux:table.cell>
                                <flux:table.cell>
                                    @forelse($user->skills as $skill)
                                        @php
                                            $skillLevel = \App\Enums\SkillLevel::from($skill->pivot->skill_level);
                                        @endphp
                                        @if ($loop->index < $maxDisplayedSkills)
                                            <flux:tooltip>
                                                <flux:badge size="sm" variant="outline" inset="top bottom"
                                                    :color="$skillLevel->getColor()" class="cursor-help">
                                                    {{ $skill->name }}
                                                </flux:badge>
                                                <flux:tooltip.content>
                                                    <div class="text-center">
                                                        <div class="font-semibold">{{ $skillLevel->getDisplayName() }}
                                                        </div>
                                                    </div>
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        @endif
                                    @empty
                                        <flux:text class="text-sm">No skills assigned</flux:text>
                                    @endforelse

                                    @if ($user->skills->count() > $maxDisplayedSkills)
                                        <flux:tooltip>
                                            <flux:badge size="sm" variant="outline" color="gray"
                                                class="cursor-help">
                                                +{{ $user->skills->count() - $maxDisplayedSkills }} more
                                            </flux:badge>
                                            <flux:tooltip.content>
                                                <div class="max-w-xs max-h-48 overflow-y-auto space-y-1">
                                                    @foreach ($user->skills->skip($maxDisplayedSkills) as $skill)
                                                        @php
                                                            $skillLevel = \App\Enums\SkillLevel::from(
                                                                $skill->pivot->skill_level,
                                                            );
                                                        @endphp
                                                        <div class="flex">
                                                            <flux:tooltip>
                                                                <flux:badge size="sm" variant="outline"
                                                                    :color="$skillLevel->getColor()"
                                                                    class="cursor-help">
                                                                    {{ $skill->name }}
                                                                </flux:badge>
                                                                <flux:tooltip.content>
                                                                    <div class="text-center">
                                                                        <div class="font-semibold">
                                                                            {{ $skillLevel->getDisplayName() }}</div>
                                                                    </div>
                                                                </flux:tooltip.content>
                                                            </flux:tooltip>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </flux:tooltip.content>
                                        </flux:tooltip>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:modal.trigger wire:click="openUserSkillModal({{ $user->id }})">
                                        <flux:button size="sm">
                                            Manage Skills
                                        </flux:button>
                                    </flux:modal.trigger>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3">No users found</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:tab.panel>
    </flux:tab.group>

    <flux:modal name="add-skill-form" variant="flyout">
        <form wire:submit="saveSkill">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add New Skill</flux:heading>
                    <flux:text class="mt-2">Add a new skill to the system.</flux:text>
                </div>

                <div class="space-y-4 max-w-sm">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="skillForm.name" placeholder="Enter skill name" />
                        <flux:error name="skillForm.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="skillForm.description" placeholder="Enter skill description" />
                        <flux:error name="skillForm.description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:autocomplete wire:model="skillForm.category" placeholder="Search or enter skill category">
                            @foreach ($filteredCategories as $category)
                                <flux:autocomplete.item>{{ $category }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="skillForm.category" />
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Create Skill</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-skill-form" variant="flyout">
        <form wire:submit="saveSkill">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit Skill</flux:heading>
                    <flux:text class="mt-2">Edit the skill details.</flux:text>
                </div>

                <div class="space-y-4 max-w-sm">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="skillForm.name" placeholder="Enter skill name" />
                        <flux:error name="skillForm.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="skillForm.description" placeholder="Enter skill description" />
                        <flux:error name="skillForm.description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:autocomplete wire:model="skillForm.category" placeholder="Search or enter skill category">
                            @foreach ($filteredCategories as $category)
                                <flux:autocomplete.item>{{ $category }}</flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        <flux:error name="skillForm.category" />
                    </flux:field>
                </div>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Update Skill</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="user-skills-form" variant="flyout">
        <form wire:submit.prevent>
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex items-center gap-2">Manage Skills for <flux:text
                            class="font-bold text-base" color="blue">
                            {{ $userSkillForm->user?->full_name ?? '' }}</flux:text>
                    </flux:heading>
                    <flux:text class="mt-2">Add new skills or manage existing skill levels.</flux:text>
                </div>

                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Search and Select Skill</flux:label>
                            <flux:input type="text" wire:model.live="skillSearchForAssignment"
                                placeholder="Type to search skills..." class="w-full" />
                        </flux:field>

                        @if ($skillSearchForAssignment)
                            @if ($filteredSkills->count() > 0)
                                <div class="max-h-48 overflow-y-auto space-y-2">
                                    @foreach ($filteredSkills as $skill)
                                        @php
                                            $userHasSkill =
                                                $userSkillForm->user && $userSkillForm->user->skills->contains('id', $skill->id);
                                        @endphp
                                        <flux:card
                                            class="hover:bg-zinc-50 dark:hover:bg-zinc-700 p-4 {{ $userHasSkill ? 'opacity-60' : 'cursor-pointer' }}"
                                            wire:click="{{ $userHasSkill ? '' : 'toggleSkillSelection(' . $skill->id . ')' }}">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <flux:text class="font-medium">{{ $skill->name }}</flux:text>
                                                        @if ($userHasSkill)
                                                            <flux:badge size="sm" variant="outline"
                                                                color="green">
                                                                Already Added
                                                            </flux:badge>
                                                        @endif
                                                    </div>
                                                    <flux:text class="text-sm" variant="subtle">
                                                        {{ $skill->description }}</flux:text>
                                                </div>
                                                @if (!$userHasSkill)
                                                    <flux:button type="button" size="sm" variant="ghost"
                                                        icon="{{ $selectedSkillForAssignment && $selectedSkillForAssignment->id === $skill->id ? 'minus' : 'plus' }}" />
                                                @endif
                                            </div>

                                            @if (!$userHasSkill && $selectedSkillForAssignment && $selectedSkillForAssignment->id === $skill->id)
                                                <div class="pt-4" @click.stop>

                                                    <flux:field>
                                                        <flux:label>Skill Level</flux:label>
                                                        <flux:select wire:model.live="newSkillLevel" @click.stop>
                                                            @foreach (\App\Enums\SkillLevel::cases() as $level)
                                                                <flux:select.option value="{{ $level->value }}">
                                                                    {{ $level->getDisplayName() }}</flux:select.option>
                                                            @endforeach
                                                        </flux:select>
                                                    </flux:field>
                                                    <div
                                                        class="flex mt-6
                                                         justify-end">
                                                        <flux:button type="button" size="sm" variant="primary"
                                                            wire:click="addSkillWithLevel" @click.stop>
                                                            Add Skill
                                                        </flux:button>
                                                        <flux:button type="button" size="sm" variant="ghost"
                                                            wire:click="cancelSkillSelection" @click.stop>
                                                            Cancel
                                                        </flux:button>
                                                    </div>
                                                </div>
                                            @endif
                                        </flux:card>
                                    @endforeach
                                </div>
                            @endif

                            <flux:card class="p-4 border-t border-zinc-200 dark:border-zinc-700 mt-4">
                                <div class="text-center">
                                    <flux:text class="text-sm text-zinc-500 mb-4">
                                        Couldn't find the skill you're looking for?
                                    </flux:text>
                                    <flux:button type="button" size="sm" variant="ghost"
                                        wire:click="toggleCreateSkillForm">
                                        Create "{{ $skillSearchForAssignment }}" skill
                                    </flux:button>
                                </div>

                                @if ($showCreateSkillForm)
                                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                                        <div class="space-y-4">
                                            <flux:heading>Create New Skill</flux:heading>

                                            <div class="grid grid-cols-1 gap-4">
                                                <flux:field>
                                                    <flux:label>Skill Name</flux:label>
                                                    <flux:input wire:model.live="userSkillForm.newSkillName"
                                                        placeholder="Enter skill name" />
                                                    <flux:error name="userSkillForm.newSkillName" />
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Description</flux:label>
                                                    <flux:textarea wire:model.live="userSkillForm.newSkillDescription"
                                                        placeholder="Enter skill description" />
                                                    <flux:error name="userSkillForm.newSkillDescription" />
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Category</flux:label>
                                                    <flux:autocomplete wire:model.live="userSkillForm.newSkillCategory"
                                                        placeholder="Search or enter skill category">
                                                        @foreach ($filteredCategories as $category)
                                                            <flux:autocomplete.item>{{ $category }}
                                                            </flux:autocomplete.item>
                                                        @endforeach
                                                    </flux:autocomplete>
                                                    <flux:error name="userSkillForm.newSkillCategory" />
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label>Skill Level</flux:label>
                                                    <flux:select wire:model.live="userSkillForm.newSkillLevel" required>
                                                        @foreach (\App\Enums\SkillLevel::cases() as $level)
                                                            <flux:select.option value="{{ $level->value }}">
                                                                {{ $level->getDisplayName() }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                    <flux:error name="userSkillForm.newSkillLevel" />
                                                </flux:field>
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <flux:button type="button" size="sm" variant="ghost"
                                                    wire:click="toggleCreateSkillForm">
                                                    Cancel
                                                </flux:button>
                                                <flux:button type="button" size="sm" variant="primary"
                                                    wire:click="createAndAssignSkill">
                                                    Create & Assign Skill
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </flux:card>
                        @endif
                    </div>


                    <div class="space-y-4">
                        <flux:heading size="md">User Skills</flux:heading>


                        @if ($userSkillForm->user && $userSkillForm->user->skills->count() > 0)

                            <div class="space-y-2">
                                @foreach ($userSkillForm->user->skills as $skill)
                                    <flux:card class="hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer p-4">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <flux:text class="font-medium block" variant="strong">
                                                    {{ $skill->name }}</flux:text> |
                                                <flux:text class="text-sm" variant="subtle">
                                                    {{ $skill->skill_category }}</flux:text>
                                            </div>
                                            <flux:text class="text-sm">{{ $skill->description }}</flux:text>
                                        </div>
                                        <div class="flex items-center gap-4 mt-4" @click.stop>

                                            <flux:select wire:model.live="userSkillForm.skillLevels.{{ $skill->id }}"
                                                wire:change="updateSkillLevel({{ $skill->id }}, $event.target.value)"
                                                @click.stop>
                                                @foreach (\App\Enums\SkillLevel::cases() as $level)
                                                    <flux:select.option value="{{ $level->value }}">
                                                        {{ $level->getDisplayName() }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:button size="xs"
                                                wire:click="removeUserSkill({{ $skill->id }})" icon="trash"
                                                @click.stop>
                                            </flux:button>
                                        </div>
                                    </flux:card>
                                @endforeach
                            </div>
                        @else
                            <flux:text>No skills assigned yet.</flux:text>
                        @endif
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </form>
    </flux:modal>

</div>
