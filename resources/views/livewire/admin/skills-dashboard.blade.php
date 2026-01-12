<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Skills Dashboard</flux:heading>
            <flux:text class="mt-2">Team capability overview and insights.</flux:text>
        </div>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="overview">Overview</flux:tab>
            <flux:tab name="category">By Category</flux:tab>
            <flux:tab name="team">Team</flux:tab>
            <flux:tab name="matrix">Matrix</flux:tab>
        </flux:tabs>

        {{-- Overview Tab --}}
        <flux:tab.panel name="overview" class="pt-6">
            {{-- Summary Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <flux:card class="!p-4">
                    <flux:text class="text-sm">Team Members</flux:text>
                    <flux:heading size="xl">{{ $this->teamMemberCount }}</flux:heading>
                </flux:card>
                <flux:card class="!p-4">
                    <flux:text class="text-sm">Skills Used</flux:text>
                    <flux:heading size="xl">{{ $this->skillsUsedCount }}/{{ $this->totalApprovedSkills }}</flux:heading>
                </flux:card>
                <flux:card class="!p-4">
                    <flux:text class="text-sm">Avg per User</flux:text>
                    <flux:heading size="xl">{{ $this->averageSkillsPerUser }}</flux:heading>
                </flux:card>
                <flux:card class="!p-4">
                    <flux:text class="text-sm">Changes /30d</flux:text>
                    <flux:heading size="xl">{{ $this->changesLast30Days }}</flux:heading>
                </flux:card>
            </div>

            {{-- Category Strength --}}
            <flux:card class="mb-8">
                <flux:heading size="lg" class="mb-4">Category Strength</flux:heading>

                @forelse ($this->categoryStrength as $category)
                    <div wire:key="cat-{{ $category['id'] }}" class="mb-4 last:mb-0">
                        <div class="flex justify-between items-center mb-1">
                            <flux:text class="font-medium">{{ $category['name'] }}</flux:text>
                            <flux:text class="text-sm">
                                {{ $category['userCount'] }} {{ $category['userCount'] === 1 ? 'person' : 'people' }},
                                {{ $category['usedSkills'] }}/{{ $category['totalSkills'] }} skills
                            </flux:text>
                        </div>
                        <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-sky-500 dark:bg-sky-400 rounded-full transition-all duration-300"
                                style="width: {{ $category['percentage'] }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <flux:text>No categories defined yet.</flux:text>
                @endforelse
            </flux:card>

            {{-- Two Column: Needs Attention + Recent Activity --}}
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Needs Attention --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Needs Attention</flux:heading>

                    @forelse ($this->needsAttention as $item)
                        <div wire:key="attention-{{ $loop->index }}" class="flex items-start gap-2 mb-3 last:mb-0">
                            @if ($item['severity'] === 'high')
                                <flux:icon name="exclamation-triangle" variant="mini" class="text-red-500 mt-0.5 shrink-0" />
                            @elseif ($item['severity'] === 'medium')
                                <flux:icon name="exclamation-circle" variant="mini" class="text-amber-500 mt-0.5 shrink-0" />
                            @else
                                <flux:icon name="information-circle" variant="mini" class="text-sky-500 mt-0.5 shrink-0" />
                            @endif
                            <div>
                                <flux:text class="font-medium">{{ $item['skill'] }}</flux:text>
                                <flux:text class="text-sm">{{ $item['issue'] }}</flux:text>
                            </div>
                        </div>
                    @empty
                        <div class="flex items-center gap-2">
                            <flux:icon name="check-circle" variant="mini" class="text-green-500" />
                            <flux:text>All skills have good coverage!</flux:text>
                        </div>
                    @endforelse
                </flux:card>

                {{-- Recent Activity --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Recent Activity</flux:heading>

                    @forelse ($this->recentActivity as $activity)
                        <div wire:key="activity-{{ $loop->index }}" class="flex items-start gap-2 mb-3 last:mb-0">
                            <flux:badge size="sm" color="{{ $activity['colour'] }}">{{ $activity['event'] }}</flux:badge>
                            <flux:text class="text-sm">
                                {{ $activity['user'] }} - {{ $activity['skill'] }}
                                @if ($activity['level'])
                                    ({{ $activity['level'] }})
                                @endif
                                <span class="text-zinc-400">{{ $activity['time'] }}</span>
                            </flux:text>
                        </div>
                    @empty
                        <flux:text>No recent activity.</flux:text>
                    @endforelse
                </flux:card>
            </div>
        </flux:tab.panel>

        {{-- By Category Tab (placeholder) --}}
        <flux:tab.panel name="category" class="pt-6">
            <flux:text>By Category view - coming soon.</flux:text>
        </flux:tab.panel>

        {{-- Team Tab (placeholder) --}}
        <flux:tab.panel name="team" class="pt-6">
            <flux:text>Team directory view - coming soon.</flux:text>
        </flux:tab.panel>

        {{-- Matrix Tab (placeholder) --}}
        <flux:tab.panel name="matrix" class="pt-6">
            <flux:text>Enhanced matrix view - coming soon.</flux:text>
        </flux:tab.panel>
    </flux:tab.group>
</div>
