<div>
    @if ($this->users->count() > 0 && $this->skills->count() > 0)
        <div class="overflow-x-auto">
            <div class="inline-grid gap-px bg-zinc-200 dark:bg-zinc-700" style="grid-template-columns: auto repeat({{ $this->skills->count() }}, 1.75rem);">
                {{-- Header row --}}
                <div class="bg-zinc-50 dark:bg-zinc-800 p-1"></div>
                @foreach ($this->skills as $skill)
                    <flux:tooltip content="{{ $skill['fullName'] }}" position="top">
                        <div
                            wire:key="header-{{ $skill['id'] }}"
                            class="bg-zinc-50 dark:bg-zinc-800 p-1 text-center cursor-default"
                        >
                            <span class="text-[10px] font-medium text-zinc-600 dark:text-zinc-400">
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
                            class="bg-zinc-50 dark:bg-zinc-800 px-1.5 py-1 cursor-default"
                        >
                            <span class="text-[10px] font-medium text-zinc-700 dark:text-zinc-300">
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
                                    class="h-7 {{ $bgClass }} cursor-default"
                                ></div>
                            </flux:tooltip>
                        @else
                            <div
                                wire:key="cell-{{ $user['id'] }}-{{ $skill['id'] }}"
                                class="h-7 {{ $bgClass }}"
                            ></div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Compact legend --}}
        <div class="mt-3 flex flex-wrap gap-3 text-xs">
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-green-500 dark:bg-green-600"></div>
                <span class="text-zinc-600 dark:text-zinc-400">High</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-sky-500 dark:bg-sky-600"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Medium</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-zinc-400 dark:bg-zinc-500"></div>
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
