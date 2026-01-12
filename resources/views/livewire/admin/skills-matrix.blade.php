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

            <flux:button wire:click="export" icon="arrow-down-tray">Export</flux:button>
        </div>
    </div>

    <flux:field class="mb-6">
        <flux:label>
            Time travel
            <x-slot name="trailing">
                <span class="tabular-nums">{{ $this->viewingDate->format('j M Y') }}</span>
            </x-slot>
        </flux:label>
        <flux:slider wire:model.live="timelinePosition" min="0" max="{{ $this->timelineMax }}" />
    </flux:field>

    @if ($this->users->count() > 0 && $this->skills->count() > 0)
        <div class="overflow-x-auto">
            <div wire:transition class="inline-grid gap-2" style="grid-template-columns: auto repeat({{ $this->skills->count() }}, 3.5rem);">
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
                    <div wire:key="name-{{ $user->id }}" class="">
                        <flux:text>{{ $user->full_name }}</flux:text>
                    </div>
                    @foreach ($this->skills as $skill)
                        <div
                            wire:key="cell-{{ $user->id }}-{{ $skill->id }}"
                            class="h-8 {{ $user->getSkillLevelAt($skill, $this->viewingDate)?->bgClass() ?? 'bg-zinc-100 dark:bg-zinc-800' }}"
                        ></div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded {{ \App\Enums\SkillLevel::Low->bgClass() }}"></div>
                <flux:text>Low</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded {{ \App\Enums\SkillLevel::Medium->bgClass() }}"></div>
                <flux:text>Medium</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded {{ \App\Enums\SkillLevel::High->bgClass() }}"></div>
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
