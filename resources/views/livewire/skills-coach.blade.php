<div class="flex flex-col h-[calc(100vh-12rem)]">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Skills Coach</flux:heading>
            <flux:text class="mt-2">Get personalised advice about your skills development.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @if (count($messages) > 0)
                <flux:button wire:click="clearChat" variant="subtle" icon="trash" size="sm">
                    Clear Chat
                </flux:button>
            @endif
            <flux:button :href="route('home')" variant="subtle" icon="arrow-left" size="sm">
                Back
            </flux:button>
        </div>
    </div>

    <flux:separator variant="subtle" class="mb-6" />

    <flux:card class="flex-1 overflow-y-auto mb-6">
        <div class="space-y-6">
            @forelse ($messages as $index => $message)
                @if ($message['role'] === 'user')
                    <div wire:key="message-{{ $index }}" class="flex justify-end">
                        <div class="max-w-[80%] bg-zinc-100 dark:bg-zinc-700 rounded-2xl px-4 py-3">
                            <flux:text>{{ $message['content'] }}</flux:text>
                        </div>
                    </div>
                @else
                    <div wire:key="message-{{ $index }}" class="max-w-[90%]">
                        <flux:text>{{ $message['content'] }}</flux:text>
                    </div>
                @endif
            @empty
                <div class="flex items-center justify-center h-full min-h-64">
                    <div class="text-center">
                        <flux:icon name="sparkles" class="w-12 h-12 mx-auto text-amber-500 mb-4" />
                        <flux:heading size="lg" class="mb-2">Welcome to Skills Coach</flux:heading>
                        <flux:text class="text-zinc-500">
                            Ask me anything about your skills, career development, or what to learn next.
                        </flux:text>
                    </div>
                </div>
            @endforelse
        </div>
    </flux:card>

    <form wire:submit="send">
        <flux:composer wire:model="prompt" label="Message" label:sr-only placeholder="Ask me about your skills...">
            <x-slot name="actionsLeading">
                <flux:button size="sm" variant="subtle" icon="paper-clip" />
                <flux:button size="sm" variant="subtle" icon="slash" />
                <flux:button size="sm" variant="subtle" icon="adjustments-horizontal" />
            </x-slot>
            <x-slot name="actionsTrailing">
                <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" />
            </x-slot>
        </flux:composer>
        <flux:error name="prompt" class="mt-2" />
    </form>
</div>
