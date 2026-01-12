<div
    x-data="{
        playing: false,
        interval: null,
        speed: 150,
        play() {
            if (this.playing) {
                this.pause();
                return;
            }
            this.playing = true;
            this.interval = setInterval(() => {
                let current = $wire.timelinePosition;
                let max = {{ $this->timelineMax }};
                if (current >= max) {
                    $wire.timelinePosition = 0;
                } else {
                    $wire.timelinePosition = current + 1;
                }
            }, this.speed);
        },
        pause() {
            this.playing = false;
            clearInterval(this.interval);
        }
    }"
    x-on:keydown.escape.window="pause()"
>
    @if ($this->users->count() > 0 && $this->skills->count() > 0)
        {{-- Time controls --}}
        <div class="mb-4 flex items-end gap-3">
            <div class="flex-1">
                <flux:field>
                    <flux:label class="text-sm">
                        Time travel
                        <x-slot name="trailing">
                            <span class="tabular-nums font-medium">{{ $this->viewingDate->format('j M Y') }}</span>
                        </x-slot>
                    </flux:label>
                    <flux:slider wire:model.live="timelinePosition" min="0" max="{{ $this->timelineMax }}" />
                </flux:field>
            </div>
            <div class="flex gap-1">
                <flux:button
                    x-on:click="play()"
                    size="sm"
                    variant="ghost"
                    x-bind:title="playing ? 'Pause' : 'Play'"
                    class="!px-2"
                >
                    <flux:icon x-show="!playing" name="play" variant="mini" x-cloak />
                    <flux:icon x-show="playing" name="pause" variant="mini" x-cloak />
                </flux:button>
                <flux:button
                    x-on:click="$wire.timelinePosition = 0"
                    size="sm"
                    variant="ghost"
                    title="Reset to start"
                    class="!px-2"
                >
                    <flux:icon name="backward" variant="mini" />
                </flux:button>
                <flux:button
                    x-on:click="$wire.timelinePosition = {{ $this->timelineMax }}"
                    size="sm"
                    variant="ghost"
                    title="Jump to now"
                    class="!px-2"
                >
                    <flux:icon name="forward" variant="mini" />
                </flux:button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div wire:transition class="inline-grid gap-px bg-zinc-200 dark:bg-zinc-700" style="grid-template-columns: auto repeat({{ $this->skills->count() }}, 1.1rem);">
                {{-- Header row --}}
                <div class="bg-zinc-50 dark:bg-zinc-800 p-0.5"></div>
                @foreach ($this->skills as $skill)
                    <flux:tooltip content="{{ $skill['fullName'] }}" position="top">
                        <div
                            wire:key="header-{{ $skill['id'] }}"
                            class="bg-zinc-50 dark:bg-zinc-800 p-0.5 text-center cursor-default"
                        >
                            <span class="text-[8px] font-medium text-zinc-600 dark:text-zinc-400 leading-none">
                                {{ $skill['abbr'] }}
                            </span>
                        </div>
                    </flux:tooltip>
                @endforeach

                {{-- Data rows --}}
                @foreach ($this->users as $user)
                    <flux:tooltip content="{{ $user['fullName'] }}" position="left">
                        <div
                            wire:key="name-{{ $user['id'] }}"
                            class="bg-zinc-50 dark:bg-zinc-800 px-1 py-0.5 cursor-default"
                        >
                            <span class="text-[8px] font-medium text-zinc-700 dark:text-zinc-300 leading-none">
                                {{ $user['initials'] }}
                            </span>
                        </div>
                    </flux:tooltip>
                    @foreach ($this->skills as $skill)
                        @php
                            $level = $user['skills'][$skill['id']] ?? null;
                            $bgClass = match($level) {
                                \App\Enums\SkillLevel::High->value => 'bg-green-500 dark:bg-green-600',
                                \App\Enums\SkillLevel::Medium->value => 'bg-sky-500 dark:bg-sky-600',
                                \App\Enums\SkillLevel::Low->value => 'bg-zinc-400 dark:bg-zinc-500',
                                default => 'bg-zinc-100 dark:bg-zinc-800',
                            };
                            $levelLabel = match($level) {
                                \App\Enums\SkillLevel::High->value => 'High',
                                \App\Enums\SkillLevel::Medium->value => 'Medium',
                                \App\Enums\SkillLevel::Low->value => 'Low',
                                default => null,
                            };
                        @endphp
                        @if ($levelLabel)
                            <flux:tooltip content="{{ $user['fullName'] }}: {{ $skill['fullName'] }} ({{ $levelLabel }})" position="top">
                                <div
                                    wire:key="cell-{{ $user['id'] }}-{{ $skill['id'] }}"
                                    class="h-4 {{ $bgClass }} cursor-default"
                                ></div>
                            </flux:tooltip>
                        @else
                            <div
                                wire:key="cell-{{ $user['id'] }}-{{ $skill['id'] }}"
                                class="h-4 {{ $bgClass }}"
                            ></div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Compact legend --}}
        <div class="mt-3 flex flex-wrap gap-3 text-xs">
            <div class="flex items-center gap-1">
                <div class="w-3 h-3 rounded-sm bg-green-500 dark:bg-green-600"></div>
                <span class="text-zinc-600 dark:text-zinc-400">High</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="w-3 h-3 rounded-sm bg-sky-500 dark:bg-sky-600"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Medium</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="w-3 h-3 rounded-sm bg-zinc-400 dark:bg-zinc-500"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Low</span>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <flux:icon name="table-cells" class="w-10 h-10 mx-auto mb-3 text-zinc-400" />
            <flux:text>No data to display.</flux:text>
        </div>
    @endif
</div>
