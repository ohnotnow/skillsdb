<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6">
        <flux:heading size="xl" level="1">Manage User Skills</flux:heading>
    </div>

    <div class="mb-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search users by name, email, or username..."
            icon="magnifying-glass"
        />
    </div>

    @if ($this->users->count() > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Email</flux:table.column>
                <flux:table.column>Skills</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Last Updated</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row wire:key="user-{{ $user->id }}">
                        <flux:table.cell class="font-medium">
                            <flux:link href="{{ route('admin.users.skills', $user) }}" wire:navigate>
                                {{ $user->full_name }}
                            </flux:link>
                            @if ($user->is_admin)
                                <flux:badge size="sm" color="purple" class="ml-2">Admin</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>{{ $user->skills_count }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if ($user->last_updated_skills_at)
                                {{ $user->last_updated_skills_at->diffForHumans() }}
                            @else
                                <span class="text-zinc-400">Never</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="text-center py-12">
            <flux:icon name="users" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No users found</flux:heading>
            <flux:text>
                @if ($search)
                    No users match your search. Try a different term.
                @else
                    No users have been created yet.
                @endif
            </flux:text>
        </div>
    @endif
</div>
