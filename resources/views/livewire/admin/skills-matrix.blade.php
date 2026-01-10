<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Skills Matrix</flux:heading>
            <flux:text class="mt-2">Overview of all team members and their skill levels.</flux:text>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <flux:select variant="listbox" multiple wire:model.live="selectedSkills" placeholder="Filter by skills...">
                @foreach ($this->allSkills as $skill)
                    <flux:select.option value="{{ $skill->id }}">{{ $skill->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select variant="listbox" multiple wire:model.live="selectedUsers" placeholder="Filter by users...">
                @foreach ($this->allUsers as $user)
                    <flux:select.option value="{{ $user->id }}">{{ $user->full_name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($this->users->count() > 0 && $this->skills->count() > 0)
        <div class="overflow-x-auto">
            <div class="inline-grid gap-px" style="grid-template-columns: auto repeat({{ $this->skills->count() }}, 3.5rem);">
                {{-- Header row --}}
                <div class="p-2 font-medium self-end">
                    <flux:text>Name</flux:text>
                </div>
                @foreach ($this->skills as $skill)
                    <div wire:key="header-{{ $skill->id }}" class="h-32 relative">
                        <div class="absolute bottom-2 left-4 origin-bottom-left -rotate-45 whitespace-nowrap text-sm font-medium">
                            {{ $skill->name }}
                        </div>
                    </div>
                @endforeach

                {{-- Data rows --}}
                @foreach ($this->users as $user)
                    <div wire:key="name-{{ $user->id }}" class="p-2">
                        <flux:text>{{ $user->full_name }}</flux:text>
                    </div>
                    @foreach ($this->skills as $skill)
                        <div wire:key="cell-{{ $user->id }}-{{ $skill->id }}" class="p-2">
                            @php
                                $level = $user->getSkillLevel($skill);
                            @endphp
                            @if ($level)
                                <flux:badge size="sm" color="{{ $level->colour() }}">
                                    {{ substr($level->label(), 0, 1) }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" icon="minus-circle"></flux:badge>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="amber">L</flux:badge>
                <flux:text>Low</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="sky">M</flux:badge>
                <flux:text>Medium</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="green">H</flux:badge>
                <flux:text>High</flux:text>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <flux:icon name="table-cells" class="w-12 h-12 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">No data to display</flux:heading>
            <flux:text>
                @if ($this->users->count() === 0)
                    No users have been created yet.
                @else
                    No approved skills exist yet. Add some skills first.
                @endif
            </flux:text>
        </div>
    @endif
</div>
