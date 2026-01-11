<div class="container mx-auto p-8">
    <flux:heading size="xl" class="mb-6">🎮 Skill History Play Space</flux:heading>

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
</div>
