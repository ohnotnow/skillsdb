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

            {{-- Colleague insights --}}
            @if($this->colleagueInsights->isNotEmpty())
                <flux:separator class="my-4" />
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
                <flux:separator variant="subtle" class="my-4" />
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
        </flux:card>
    @endunless
</div>
