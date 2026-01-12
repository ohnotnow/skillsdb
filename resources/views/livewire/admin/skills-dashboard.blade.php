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

        {{-- By Category Tab --}}
        <flux:tab.panel name="category" class="pt-6">
            <flux:accordion transition>
                @forelse ($this->categoriesWithSkills as $category)
                    <flux:accordion.item wire:key="acc-cat-{{ $category['id'] }}">
                        <flux:accordion.heading>
                            <div class="flex items-center justify-between w-full pr-4">
                                <span>{{ $category['name'] }}</span>
                                <flux:text class="text-sm">
                                    {{ $category['userCount'] }} {{ $category['userCount'] === 1 ? 'person' : 'people' }} across {{ $category['skillCount'] }} skills
                                </flux:text>
                            </div>
                        </flux:accordion.heading>
                        <flux:accordion.content>
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pt-2">
                                @foreach ($category['skills'] as $skill)
                                    <flux:card wire:key="skill-card-{{ $skill['id'] }}" class="!p-4">
                                        <flux:heading size="sm" class="mb-2">{{ $skill['name'] }}</flux:heading>
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="flex-1 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                                @php $maxUsers = max($this->teamMemberCount, 1); @endphp
                                                <div
                                                    class="h-full bg-sky-500 dark:bg-sky-400 rounded-full"
                                                    style="width: {{ min(100, ($skill['userCount'] / $maxUsers) * 100) }}%"
                                                ></div>
                                            </div>
                                            <flux:text class="text-sm tabular-nums">{{ $skill['userCount'] }}</flux:text>
                                        </div>
                                        <div class="flex gap-2 text-xs">
                                            @if ($skill['high'] > 0)
                                                <flux:badge size="sm" color="green">{{ $skill['high'] }} High</flux:badge>
                                            @endif
                                            @if ($skill['medium'] > 0)
                                                <flux:badge size="sm" color="sky">{{ $skill['medium'] }} Med</flux:badge>
                                            @endif
                                            @if ($skill['low'] > 0)
                                                <flux:badge size="sm" color="zinc">{{ $skill['low'] }} Low</flux:badge>
                                            @endif
                                            @if ($skill['userCount'] === 0)
                                                <flux:badge size="sm" color="amber">No one</flux:badge>
                                            @endif
                                        </div>
                                    </flux:card>
                                @endforeach
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                @empty
                    <flux:text>No categories defined yet.</flux:text>
                @endforelse
            </flux:accordion>
        </flux:tab.panel>

        {{-- Team Tab --}}
        <flux:tab.panel name="team" class="pt-6">
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <flux:input
                    wire:model.live.debounce.300ms="teamSearch"
                    placeholder="Search by name..."
                    icon="magnifying-glass"
                    class="sm:max-w-xs"
                />
                <flux:select wire:model.live="skillFilter" placeholder="Filter by skill..." class="sm:max-w-xs">
                    <flux:select.option value="">All skills</flux:select.option>
                    @foreach ($this->allSkillsForFilter as $skill)
                        <flux:select.option value="{{ $skill->id }}">{{ $skill->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="space-y-2">
                @forelse ($this->teamMembers as $member)
                    <flux:card wire:key="member-{{ $member['id'] }}" class="!p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <flux:link href="{{ route('admin.users.skills', $member['id']) }}" class="font-medium">
                                    {{ $member['name'] }}
                                </flux:link>
                                <flux:text class="text-sm">
                                    {{ $member['skillCount'] }} {{ $member['skillCount'] === 1 ? 'skill' : 'skills' }}
                                    @if ($member['skillCount'] > 0)
                                        <span class="text-zinc-400 mx-1">·</span>
                                        <span class="text-green-600 dark:text-green-400">{{ $member['high'] }}H</span>
                                        <span class="text-sky-600 dark:text-sky-400">{{ $member['medium'] }}M</span>
                                        <span class="text-zinc-500">{{ $member['low'] }}L</span>
                                    @endif
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-24 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    @php $maxSkills = max($this->totalApprovedSkills, 1); @endphp
                                    <div
                                        class="h-full bg-sky-500 dark:bg-sky-400 rounded-full"
                                        style="width: {{ min(100, ($member['skillCount'] / $maxSkills) * 100) }}%"
                                    ></div>
                                </div>
                                <flux:text class="text-sm whitespace-nowrap {{ $member['isStale'] ? 'text-amber-500' : '' }}">
                                    {!! $member['lastUpdated'] !!}
                                </flux:text>
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <flux:card class="!p-8 text-center">
                        <flux:icon name="users" class="w-12 h-12 mx-auto mb-4 text-zinc-400" />
                        <flux:text>No team members found.</flux:text>
                    </flux:card>
                @endforelse
            </div>
        </flux:tab.panel>

        {{-- Matrix Tab --}}
        <flux:tab.panel name="matrix" class="pt-6">
            <div class="mb-4 flex items-center justify-between">
                <flux:text>Compact overview. Hover for details.</flux:text>
                <flux:button href="{{ route('admin.matrix') }}" icon="arrow-top-right-on-square" variant="ghost">
                    Open Full Matrix
                </flux:button>
            </div>
            <livewire:admin.compact-matrix />
        </flux:tab.panel>
    </flux:tab.group>
</div>
