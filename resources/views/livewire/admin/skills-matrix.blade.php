<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">Skills Matrix</flux:heading>
        <flux:text class="mt-2">Overview of all team members and their skill levels.</flux:text>
    </div>

    @if ($this->users->count() > 0 && $this->skills->count() > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-48">Name</flux:table.column>
                @foreach ($this->skills as $skill)
                    <flux:table.column wire:key="header-{{ $skill->id }}" class="h-32 w-14 relative">
                        <div class="absolute bottom-2 left-4 origin-bottom-left -rotate-45 whitespace-nowrap">
                            {{ $skill->name }}
                        </div>
                    </flux:table.column>
                @endforeach
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row wire:key="row-{{ $user->id }}">
                        <flux:table.cell>{{ $user->full_name }}</flux:table.cell>
                        @foreach ($this->skills as $skill)
                            <flux:table.cell wire:key="cell-{{ $user->id }}-{{ $skill->id }}" align="start">
                                @php
                                    $level = $user->getSkillLevel($skill);
                                @endphp
                                @if ($level)
                                    <flux:badge size="sm" color="{{ $level->colour() }}">
                                        {{ substr($level->label(), 0, 1) }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>
                        @endforeach
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

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
