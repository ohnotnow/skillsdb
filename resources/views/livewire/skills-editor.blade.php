<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center mb-6">
        <div class="flex-1 w-full">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search skills by name, description, or category..."
                icon="magnifying-glass"
            />
        </div>
        <div class="flex gap-4 items-center">
            <flux:field variant="inline">
                <flux:label>Show only my skills</flux:label>
                <flux:switch wire:model.live="showMySkillsOnly" />
            </flux:field>
            <flux:button wire:click="openSuggestModal" icon="plus">
                Suggest Skill
            </flux:button>
        </div>
    </div>

    @if ($this->skills->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->skills as $skill)
                <flux:card wire:key="skill-{{ $skill->id }}">
                    <div class="flex flex-col h-full">
                        <div class="mb-3">
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading size="sm">{{ $skill->name }}</flux:heading>
                                @if ($skill->isPending())
                                    <flux:badge size="sm" color="amber">Pending</flux:badge>
                                @endif
                            </div>
                            @if ($skill->category)
                                <flux:text size="sm" class="text-zinc-500">{{ $skill->category->name }}</flux:text>
                            @endif
                        </div>

                        @if ($skill->description)
                            <flux:text size="sm" class="mb-4 flex-1">{{ $skill->description }}</flux:text>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <flux:radio.group
                            label="Level"
                            wire:model.live="userSkillLevels.{{ $skill->id }}"
                            wire:change="updateSkillLevel({{ $skill->id }}, $event.target.value)"
                            variant="pills"
                            size="sm"
                        >
                            <flux:radio label="None" value="none" />
                            @foreach ($this->skillLevels as $level)
                                <flux:radio label="{{ $level->label() }}" value="{{ $level->value }}" />
                            @endforeach
                        </flux:radio.group>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <flux:icon name="magnifying-glass" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No skills found</flux:heading>
            <flux:text>
                @if ($search || $showMySkillsOnly)
                    No skills match your current filters. Try adjusting your search or clearing filters.
                @else
                    No skills available yet. Suggest a new skill to get started.
                @endif
            </flux:text>
        </div>
    @endif

    <flux:modal wire:model="showSuggestModal" variant="flyout">
        <form wire:submit="suggestSkill">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Suggest a New Skill</flux:heading>
                    <flux:text class="mt-2">
                        Suggest a skill to be added to the system. It will be reviewed by an admin before becoming available to everyone.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Skill Name</flux:label>
                        <flux:input wire:model="newSkillName" placeholder="e.g., Kubernetes" />
                        <flux:error name="newSkillName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="newSkillDescription" placeholder="Brief description of the skill..." rows="3" />
                        <flux:error name="newSkillDescription" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Your Level</flux:label>
                        <flux:select wire:model="newSkillLevel">
                            <flux:select.option value="">Select your level...</flux:select.option>
                            @foreach ($this->skillLevels as $level)
                                <flux:select.option value="{{ $level->value }}">{{ $level->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="newSkillLevel" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeSuggestModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Suggest Skill</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
