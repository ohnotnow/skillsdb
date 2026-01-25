@props([
    'icon' => 'sparkles',
    'avatarGradient' => 'bg-gradient-to-br from-amber-400 to-orange-500',
    'avatarShadow' => 'shadow-lg shadow-amber-500/25',
    'avatarShadowSmall' => 'shadow-md shadow-amber-500/20',
    'avatarShadowLarge' => 'shadow-xl shadow-amber-500/30',
    'subtitleColor' => 'text-amber-600 dark:text-amber-400',
    'bubbleBg' => 'bg-amber-50/50 dark:bg-amber-950/20',
    'bubbleBorder' => 'border-amber-200/50 dark:border-amber-800/30',
    'suggestionColors' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/50',
    'title' => 'Coach',
    'subtitle' => '',
    'backRoute' => 'home',
    'welcomeHeading' => 'Welcome',
    'welcomeDescription' => '',
    'placeholder' => 'Ask me something...',
    'suggestions' => [],
    'messages' => [],
    'disabled' => false,
    'disabledHeading' => 'Unavailable',
    'disabledDescription' => 'This coach is not available right now.',
])

<div class="flex flex-col h-[calc(100vh-12rem)] max-w-3xl mx-auto" x-data="{ suggestions: @js($suggestions) }"
    x-init="
        $wire.on('message-sent', () => {
            $nextTick(() => $refs.chatArea.scrollTop = $refs.chatArea.scrollHeight);
        });
        $wire.on('message-received', () => {
            $nextTick(() => {
                $refs.chatArea.scrollTop = $refs.chatArea.scrollHeight;
                $refs.composer.querySelector('textarea')?.focus();
            });
        });
    ">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3 min-w-0">
            {{-- Coach Avatar --}}
            <div class="relative shrink-0">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full {{ $avatarGradient }} flex items-center justify-center {{ $avatarShadow }}">
                    <flux:icon :name="$icon" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white dark:border-zinc-900"></div>
            </div>
            <div class="min-w-0">
                <flux:heading size="lg" level="1" class="truncate">{{ $title }}</flux:heading>
                @if ($subtitle)
                    <flux:text class="{{ $subtitleColor }} text-xs sm:text-sm font-medium">{{ $subtitle }}</flux:text>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
            @if (count($messages) > 0)
                <flux:button wire:click="clearChat" variant="subtle" icon="arrow-path" size="sm" class="max-sm:!px-2">
                    <span class="hidden sm:inline">New chat</span>
                </flux:button>
            @endif
            <flux:button :href="route($backRoute)" variant="subtle" icon="arrow-left" size="sm" class="max-sm:!px-2">
                <span class="hidden sm:inline">Back</span>
            </flux:button>
        </div>
    </div>

    <flux:separator variant="subtle" class="mb-6" />

    {{-- Chat Area --}}
    <div x-ref="chatArea" class="flex-1 overflow-y-auto mb-6 space-y-5">
        @if ($disabled)
            {{-- Disabled State --}}
            <div class="flex items-center justify-center h-full min-h-64 px-4">
                <div class="text-center max-w-sm">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mx-auto mb-5">
                        <flux:icon :name="$icon" class="w-8 h-8 sm:w-10 sm:h-10 text-zinc-400" />
                    </div>
                    <flux:heading size="lg" class="mb-2">{{ $disabledHeading }}</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">
                        {{ $disabledDescription }}
                    </flux:text>
                </div>
            </div>
        @else
            @forelse ($messages as $index => $message)
                @if ($message['role'] === 'user')
                    {{-- User Message --}}
                    <div wire:key="message-{{ $index }}" class="flex items-start gap-2.5 justify-end pl-8">
                        <div class="bg-zinc-100 dark:bg-zinc-700 rounded-2xl px-4 py-2.5 border border-zinc-200 dark:border-zinc-600">
                            <p class="text-zinc-800 dark:text-zinc-100 text-sm sm:text-base">{{ $message['content'] }}</p>
                        </div>
                        <div class="shrink-0 w-7 h-7 mt-1.5 rounded-full bg-zinc-300 dark:bg-zinc-600 flex items-center justify-center">
                            <flux:icon name="user" variant="micro" class="w-3.5 h-3.5 text-zinc-600 dark:text-zinc-300" />
                        </div>
                    </div>
                @else
                    {{-- Coach Message --}}
                    <div wire:key="message-{{ $index }}" class="flex items-start gap-2.5 pr-8">
                        <div class="shrink-0 w-7 h-7 mt-1.5 rounded-full {{ $avatarGradient }} flex items-center justify-center {{ $avatarShadowSmall }}">
                            <flux:icon :name="$icon" variant="micro" class="w-3.5 h-3.5 text-white" />
                        </div>
                        <div class="{{ $bubbleBg }} rounded-2xl px-4 py-2.5 border {{ $bubbleBorder }} coach-markdown text-zinc-700 dark:text-zinc-300 text-sm sm:text-base leading-relaxed">
                            {!! Str::markdown($message['content'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </div>
                    </div>
                @endif
            @empty
                {{-- Welcome State --}}
                <div class="flex items-center justify-center h-full min-h-64 px-4">
                    <div class="text-center max-w-sm">
                        {{-- Coach Identity --}}
                        <div class="relative inline-block mb-5">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full {{ $avatarGradient }} flex items-center justify-center {{ $avatarShadowLarge }}">
                                <flux:icon :name="$icon" class="w-8 h-8 sm:w-10 sm:h-10 text-white" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 sm:w-6 sm:h-6 bg-emerald-500 rounded-full border-2 border-white dark:border-zinc-900 flex items-center justify-center">
                                <flux:icon name="check" variant="micro" class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" />
                            </div>
                        </div>

                        <flux:heading size="lg" class="mb-2">{{ $welcomeHeading }}</flux:heading>
                        <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm mb-5 leading-relaxed">
                            {{ $welcomeDescription }}
                        </flux:text>

                        {{-- Conversation Starters --}}
                        <div class="flex flex-wrap gap-2 justify-center">
                            <template x-for="(suggestion, idx) in suggestions" :key="idx">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 {{ $suggestionColors }} text-xs sm:text-sm rounded-full transition-colors cursor-pointer"
                                    x-text="suggestion"
                                    @click="$wire.set('prompt', suggestion)"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            @endforelse
        @endif
    </div>

    {{-- Composer --}}
    @unless ($disabled)
        <form x-ref="composer" wire:submit="send">
            <flux:composer wire:model="prompt" label="Message" label:sr-only :placeholder="$placeholder">
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
    @endunless
</div>
