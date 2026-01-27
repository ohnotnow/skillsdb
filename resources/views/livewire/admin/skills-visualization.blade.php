<div class="h-full flex flex-col" x-data="{ layout: 'radial' }">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Skills Map</flux:heading>
            <flux:text class="mt-2">Interactive visualization of the skills hierarchy.</flux:text>
        </div>
        <div class="flex items-center gap-4">
            <div class="inline-flex rounded-lg border border-zinc-200 dark:border-zinc-700 p-0.5 bg-zinc-100 dark:bg-zinc-800">
                <button
                    type="button"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer"
                    :class="layout === 'radial' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                    @click="layout = 'radial'; $dispatch('layout-changed', { layout: 'radial' })"
                >
                    Radial
                </button>
                <button
                    type="button"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer"
                    :class="layout === 'tree' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                    @click="layout = 'tree'; $dispatch('layout-changed', { layout: 'tree' })"
                >
                    Tree
                </button>
            </div>
            <flux:button href="{{ route('admin.skills') }}" variant="ghost" icon="arrow-left">
                Back to Skills
            </flux:button>
        </div>
    </div>

    {{-- D3 visualization container --}}
    <flux:card class="flex-1 min-h-[600px] relative">
        <div
            id="skills-visualization"
            class="absolute inset-0 overflow-hidden"
            data-hierarchy="{{ json_encode($this->hierarchyData) }}"
            wire:ignore
        >
            {{-- D3 will render here - placeholder shown until JS loads --}}
            <div class="flex items-center justify-center h-full text-zinc-400">
                <div class="text-center">
                    <flux:icon name="chart-bar" class="w-16 h-16 mx-auto mb-4" />
                    <flux:text>Visualization loading...</flux:text>
                </div>
            </div>
        </div>
    </flux:card>
</div>

@push('scripts')
    @vite('resources/js/skills-visualization.js')
@endpush
