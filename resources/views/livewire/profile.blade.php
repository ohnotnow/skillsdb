<div>
    <flux:heading size="xl" level="1">Profile</flux:heading>
    <flux:separator variant="subtle" class="mt-6" />

    <div class="flex flex-col gap-6 pt-6">

        <div class="flex-1">
            <div class="flex gap-4 items-center mb-4">
                <flux:heading size="lg" class="flex-grow">My Skills</flux:heading>
                <flux:field variant="inline">
                    <flux:label>Show only my skills</flux:label>

                    <flux:switch wire:model.live="showMySkills" />
                </flux:field>
                <flux:input wire:model.live.debounce.300ms="skillSearchQuery"
                    placeholder="Search skills by name, description, or category..." class="w-full flex-1" />
            </div>
            <div class="space-y-6">
                @if ($allSkills->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">

                        @foreach ($allSkills as $skill)
                            <flux:card class="" wire:key="skill-{{ $skill->id }}">
                                <div class="flex">
                                    <div class="flex-1">
                                        <div class="flex flex-col justify-between mb-3 pr-8">
                                            <flux:text class="font-medium block" variant="strong">{{ $skill->name }}
                                            </flux:text>
                                            <flux:text class="text-sm" variant="subtle">{{ $skill->skill_category }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </div>

                                <div class="cursor-pointer">
                                    <flux:text size="sm" class="mb-2">{{ $skill->description }}</flux:text>

                                    <flux:radio.group label="Level"
                                        wire:model.live="userSkill.{{ $skill->id }}.skill_level"
                                        wire:change="updateUserSkill({{ $skill->id }})" size="sm"
                                        variant="pills">
                                        <flux:radio label="None" value="none" />
                                        @foreach ($skillLevels as $level)
                                            <flux:radio label="{{ $level->getDisplayName() }}"
                                                value="{{ $level->value }}" />
                                        @endforeach
                                    </flux:radio.group>

                                </div>
                            </flux:card>
                        @endforeach

                    </div>
                @else
                    <div class="text-center py-12 w-full">
                        <flux:icon name="magnifying-glass" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <flux:heading size="lg" class="mb-2">No skills found</flux:heading>
                        <flux:text class="mb-6">No skills match your current search criteria. Try adjusting your
                            search query
                            or add a new skill.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
