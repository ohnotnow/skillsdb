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
            <flux:button :href="route('admin.team-coach')" icon="user-group" variant="primary">Team Coach</flux:button>
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

    @php
        $colourClasses = [
            'sky' => ['header' => 'bg-sky-200 dark:bg-sky-800', 'empty' => 'bg-sky-100/50 dark:bg-sky-900/30'],
            'emerald' => ['header' => 'bg-emerald-200 dark:bg-emerald-800', 'empty' => 'bg-emerald-100/50 dark:bg-emerald-900/30'],
            'violet' => ['header' => 'bg-violet-200 dark:bg-violet-800', 'empty' => 'bg-violet-100/50 dark:bg-violet-900/30'],
            'amber' => ['header' => 'bg-amber-200 dark:bg-amber-800', 'empty' => 'bg-amber-100/50 dark:bg-amber-900/30'],
            'rose' => ['header' => 'bg-rose-200 dark:bg-rose-800', 'empty' => 'bg-rose-100/50 dark:bg-rose-900/30'],
            'cyan' => ['header' => 'bg-cyan-200 dark:bg-cyan-800', 'empty' => 'bg-cyan-100/50 dark:bg-cyan-900/30'],
            'lime' => ['header' => 'bg-lime-200 dark:bg-lime-800', 'empty' => 'bg-lime-100/50 dark:bg-lime-900/30'],
            'fuchsia' => ['header' => 'bg-fuchsia-200 dark:bg-fuchsia-800', 'empty' => 'bg-fuchsia-100/50 dark:bg-fuchsia-900/30'],
            'orange' => ['header' => 'bg-orange-200 dark:bg-orange-800', 'empty' => 'bg-orange-100/50 dark:bg-orange-900/30'],
            'indigo' => ['header' => 'bg-indigo-200 dark:bg-indigo-800', 'empty' => 'bg-indigo-100/50 dark:bg-indigo-900/30'],
            'zinc' => ['header' => 'bg-zinc-200 dark:bg-zinc-700', 'empty' => 'bg-zinc-100 dark:bg-zinc-800'],
        ];
    @endphp

    @if ($this->users->count() > 0 && $this->skills->count() > 0)
        <div class="overflow-x-auto">
            <div wire:transition class="inline-grid gap-2" style="grid-template-columns: auto repeat({{ $this->skills->count() }}, 3.5rem);">
                {{-- Header row --}}
                <div class="p-2 font-medium self-end">
                    <flux:text>Name</flux:text>
                </div>
                @foreach ($this->skills as $skill)
                    @php
                        $catColour = $this->getCategoryColour($skill->skill_category_id);
                        $headerClass = $colourClasses[$catColour]['header'] ?? $colourClasses['zinc']['header'];
                    @endphp
                    <flux:tooltip content="{{ $skill->category?->name ?? 'Uncategorised' }}" position="top">
                        <div wire:key="header-{{ $skill->id }}" class="h-32 relative rounded-t {{ $headerClass }} cursor-default">
                            <div class="absolute bottom-2 left-4 origin-bottom-left -rotate-45 whitespace-nowrap text-sm font-medium">
                                {{ $skill->name }}
                            </div>
                        </div>
                    </flux:tooltip>
                @endforeach

                {{-- Data rows --}}
                @foreach ($this->users as $user)
                    <div wire:key="name-{{ $user->id }}" class="">
                        <flux:text>{{ $user->full_name }}</flux:text>
                    </div>
                    @foreach ($this->skills as $skill)
                        @php
                            $catColour = $this->getCategoryColour($skill->skill_category_id);
                            $emptyClass = $colourClasses[$catColour]['empty'] ?? $colourClasses['zinc']['empty'];
                            $levelClass = $this->getSkillLevelAtDate($user->id, $skill->id)?->bgClass();
                        @endphp
                        <div
                            wire:key="cell-{{ $user->id }}-{{ $skill->id }}"
                            class="h-8 {{ $levelClass ?? $emptyClass }}"
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
