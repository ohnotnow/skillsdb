<div>
    @unless($teamMode)
        <flux:card class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                {{-- Last updated --}}
                <div>
                    <flux:text class="text-sm">Last updated {!! $this->lastUpdatedText !!}</flux:text>
                </div>

                {{-- Skill distribution --}}
                @if($this->skillDistribution['total'] > 0)
                    <div class="flex items-center gap-2">
                        <flux:text class="text-sm">Your skills:</flux:text>
                        <flux:badge size="sm">{{ $this->skillDistribution['low'] }} Low</flux:badge>
                        <flux:badge size="sm">{{ $this->skillDistribution['medium'] }} Medium</flux:badge>
                        <flux:badge color="sky" size="sm">{{ $this->skillDistribution['high'] }} High</flux:badge>
                    </div>
                @else
                    <flux:text>No skills added yet</flux:text>
                @endif
            </div>

            @php
                $currentPoints = collect($this->skillsOverTime)->last()['points'] ?? 0;
                $hasPoints = $currentPoints > 0;
            @endphp

            @if($this->colleagueInsights->isNotEmpty() || $this->trendingSkills->isNotEmpty() || $hasPoints)
                <flux:separator class="my-4" />
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Left column: insights and trending --}}
                    <div class="md:col-span-2 space-y-4">
                        {{-- Colleague insights --}}
                        @if($this->colleagueInsights->isNotEmpty())
                            <div>
                                <flux:text class="mb-2">Colleagues who share your skills:</flux:text>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($this->colleagueInsights as $insight)
                                        <flux:tooltip toggleable wire:key="insight-{{ $insight['skill']->id }}">
                                            <flux:button size="sm">
                                                {{ $insight['colleagues']->count() }} {{ $insight['colleagues']->count() === 1 ? 'has' : 'have' }} {{ $insight['skill']->name }}
                                            </flux:button>
                                            <flux:tooltip.content class="max-w-[16rem]">
                                                <div class="space-y-1">
                                                    @foreach($insight['colleagues'] as $colleague)
                                                        <flux:text>{{ $colleague->full_name }}</flux:text>
                                                    @endforeach
                                                </div>
                                            </flux:tooltip.content>
                                        </flux:tooltip>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Trending skills --}}
                        @if($this->trendingSkills->isNotEmpty())
                            <div>
                                <flux:text class="text-sm mb-2">Trending in the team this month:</flux:text>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($this->trendingSkills as $skill)
                                        <flux:badge color="zinc" size="sm">
                                            {{ $skill->name }}
                                            <span class="text-zinc-400 ml-1">+{{ $skill->recent_additions_count }}</span>
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Right column: skill points chart --}}
                    @if($hasPoints)
                        <div>
                            <flux:chart :value="$this->skillsOverTime" class="h-32">
                                <flux:chart.summary class="mb-2">
                                    <div class="flex items-baseline gap-2">
                                        <flux:heading size="xl" class="tabular-nums">
                                            <flux:chart.summary.value field="points" :fallback="$currentPoints" />
                                        </flux:heading>
                                        <flux:text>points</flux:text>
                                    </div>
                                </flux:chart.summary>

                                <flux:chart.viewport class="h-20">
                                    <flux:chart.svg>
                                        <flux:chart.line field="points" class="text-sky-500 dark:text-sky-400" />
                                        <flux:chart.area field="points" class="text-sky-500/20 dark:text-sky-400/20" />
                                        <flux:chart.axis axis="x" field="month">
                                            <flux:chart.axis.tick />
                                        </flux:chart.axis>
                                        <flux:chart.axis axis="y">
                                            <flux:chart.axis.grid />
                                        </flux:chart.axis>
                                        <flux:chart.cursor />
                                    </flux:chart.svg>
                                </flux:chart.viewport>

                                <flux:chart.tooltip>
                                    <flux:chart.tooltip.heading field="month" />
                                    <flux:chart.tooltip.value field="points" label="Points" />
                                </flux:chart.tooltip>
                            </flux:chart>
                        </div>
                    @endif
                </div>
            @endif
        </flux:card>
    @endunless
</div>
