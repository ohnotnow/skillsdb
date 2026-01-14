<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">My Skills</flux:heading>
            <flux:text class="mt-2">Manage your skills and proficiency levels.</flux:text>
        </div>
        <flux:button :href="route('coach')" icon="sparkles" variant="subtle">
            Skills Coach
        </flux:button>
    </div>
    <flux:separator variant="subtle" class="my-6" />

    <livewire:skills-dashboard />

    <livewire:skills-editor />
</div>
