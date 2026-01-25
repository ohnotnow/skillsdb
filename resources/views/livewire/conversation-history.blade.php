<div class="space-y-4">
    {{-- Search --}}
    <flux:input
        wire:model.live.debounce.300ms="search"
        placeholder="Search conversations..."
        icon="magnifying-glass"
        clearable
    />

    {{-- Conversation List --}}
    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse ($this->conversations as $conversation)
            <button
                wire:key="conv-{{ $conversation->id }}"
                wire:click="selectConversation({{ $conversation->id }})"
                class="w-full text-left p-3 rounded-lg border transition-colors cursor-pointer {{ $currentConversationId === $conversation->id ? 'border-accent bg-accent/5' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
            >
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $conversation->created_at->format('M j, Y g:ia') }}
                </flux:text>
                <flux:text class="truncate block">
                    {{ Str::limit($conversation->messages->first()?->content ?? 'Empty conversation', 60) }}
                </flux:text>
            </button>
        @empty
            <div class="text-center py-8">
                <flux:icon name="chat-bubble-left-right" class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    @if ($search)
                        No conversations match your search
                    @else
                        No previous conversations
                    @endif
                </flux:text>
            </div>
        @endforelse
    </div>
</div>
