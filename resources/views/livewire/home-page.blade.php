<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">My Profile</flux:heading>
            <flux:text class="mt-2">Manage your skills and training.</flux:text>
        </div>
        <flux:button :href="route('coach')" icon="sparkles" variant="subtle">
            Skills Coach
        </flux:button>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="skills" icon="academic-cap">My Skills</flux:tab>
            <flux:tab name="training" icon="book-open">Training</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="skills">
            <livewire:skills-dashboard />
            <livewire:skills-editor />
        </flux:tab.panel>

        <flux:tab.panel name="training">
            <livewire:training-browser />
        </flux:tab.panel>
    </flux:tab.group>
</div>
