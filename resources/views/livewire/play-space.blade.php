<div class="container mx-auto p-8">
    <flux:heading size="xl" class="mb-6">Skill History Play Space</flux:heading>

    {{-- Chart Comparison --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {{-- Old (inaccurate) chart --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Old Chart (Inaccurate)</flux:heading>
            <flux:text class="text-sm mb-4">Projects current levels backwards in time</flux:text>

            @php $oldCurrentPoints = collect($oldChartData)->last()['points'] ?? 0; @endphp

            @if($oldCurrentPoints > 0)
                <flux:chart :value="$oldChartData" class="h-48">
                    <flux:chart.summary class="mb-2">
                        <div class="flex items-baseline gap-2">
                            <flux:heading size="xl" class="tabular-nums">
                                <flux:chart.summary.value field="points" :fallback="$oldCurrentPoints" />
                            </flux:heading>
                            <flux:text>points</flux:text>
                        </div>
                    </flux:chart.summary>

                    <flux:chart.viewport class="h-32">
                        <flux:chart.svg>
                            <flux:chart.line field="points" class="text-rose-500 dark:text-rose-400" />
                            <flux:chart.area field="points" class="text-rose-500/20 dark:text-rose-400/20" />
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
            @else
                <flux:text class="text-zinc-500">No data yet</flux:text>
            @endif
        </flux:card>

        {{-- New (accurate) chart --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">New Chart (Accurate)</flux:heading>
            <flux:text class="text-sm mb-4">Replays actual history events</flux:text>

            @php $newCurrentPoints = collect($newChartData)->last()['points'] ?? 0; @endphp

            @if($newCurrentPoints > 0)
                <flux:chart :value="$newChartData" class="h-48">
                    <flux:chart.summary class="mb-2">
                        <div class="flex items-baseline gap-2">
                            <flux:heading size="xl" class="tabular-nums">
                                <flux:chart.summary.value field="points" :fallback="$newCurrentPoints" />
                            </flux:heading>
                            <flux:text>points</flux:text>
                        </div>
                    </flux:chart.summary>

                    <flux:chart.viewport class="h-32">
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
            @else
                <flux:text class="text-zinc-500">No data yet</flux:text>
            @endif
        </flux:card>
    </div>

    {{-- Raw data comparison --}}
    <flux:card class="mb-8">
        <flux:heading size="lg" class="mb-4">Raw Data Comparison</flux:heading>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <flux:text class="font-medium mb-2">Old Method:</flux:text>
                <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-2 rounded">@json($oldChartData, JSON_PRETTY_PRINT)</pre>
            </div>
            <div>
                <flux:text class="font-medium mb-2">New Method:</flux:text>
                <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-2 rounded">@json($newChartData, JSON_PRETTY_PRINT)</pre>
            </div>
        </div>
    </flux:card>

    {{-- History table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">All Skill History Events</flux:heading>
        <flux:table>
        <flux:table.columns>
            <flux:table.column>When</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Skill</flux:table.column>
            <flux:table.column>Event</flux:table.column>
            <flux:table.column>Old Level</flux:table.column>
            <flux:table.column>New Level</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($history as $event)
                <flux:table.row>
                    <flux:table.cell>{{ $event->created_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>{{ $event->user->full_name }}</flux:table.cell>
                    <flux:table.cell>{{ $event->skill->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$event->event_type->colour()">
                            {{ $event->event_type->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $event->old_level ? \App\Enums\SkillLevel::from($event->old_level)->label() : '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $event->new_level ? \App\Enums\SkillLevel::from($event->new_level)->label() : '-' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        No skill history yet. Try adding some skills!
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
