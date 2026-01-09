<div>
    <flux:heading size="xl" level="1">{{ $user->full_name }}</flux:heading>
    <flux:text class="mt-2">Manage skills and proficiency levels for this user.</flux:text>
    <flux:separator variant="subtle" class="my-6" />

    <livewire:skills-editor :user-id="$user->id" />
</div>
