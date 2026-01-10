<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">API Tokens</flux:heading>
            <flux:text class="mt-2">Manage API tokens for external integrations.</flux:text>
        </div>
        <flux:modal.trigger name="create-token">
            <flux:button icon="plus">Create Token</flux:button>
        </flux:modal.trigger>
    </div>

    @if ($this->tokens->count() > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Created By</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Created</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">Last Used</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Expires</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->tokens as $token)
                    <flux:table.row wire:key="token-{{ $token->id }}">
                        <flux:table.cell class="font-medium">{{ $token->name }}</flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">{{ $token->tokenable?->short_name ?? 'Unknown' }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $token->created_at->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell class="hidden lg:table-cell">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if ($token->expires_at)
                                @if ($token->expires_at->isPast())
                                    <flux:badge size="sm" color="red">Expired</flux:badge>
                                @else
                                    {{ $token->expires_at->format('d M Y') }}
                                @endif
                            @else
                                <flux:badge size="sm" color="zinc">Never</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $token->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="text-center py-12">
            <flux:icon name="key" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No API tokens</flux:heading>
            <flux:text>Create a token to enable API access for external integrations.</flux:text>
        </div>
    @endif

    {{-- Create Token Modal --}}
    <flux:modal name="create-token" variant="flyout" @close="resetCreateModal">
        <div class="space-y-6">
            @if ($newlyCreatedToken)
                <div>
                    <flux:heading size="lg">Token Created</flux:heading>
                    <flux:text class="mt-2">Copy this token now. You won't be able to see it again.</flux:text>
                </div>

                <flux:input icon="key" :value="$newlyCreatedToken" readonly copyable />

                <flux:callout icon="exclamation-triangle" color="amber">
                    Store this token securely. It provides full API access.
                </flux:callout>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="primary">Done</flux:button>
                    </flux:modal.close>
                </div>
            @else
                <div>
                    <flux:heading size="lg">Create API Token</flux:heading>
                    <flux:text class="mt-2">Create a new token for API access.</flux:text>
                </div>

                <form wire:submit="createToken" class="space-y-4">
                    <flux:input wire:model="tokenName" label="Token Name" placeholder="e.g., CI/CD Pipeline" />

                    <flux:date-picker wire:model="expiresAt" label="Expires" :min="now()->addDay()->format('Y-m-d')" placeholder="Never (optional)" clearable />

                    <div class="flex justify-end gap-3">
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create Token</flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="deletingTokenId" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Token</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to delete this token? Any integrations using it will immediately lose access.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteToken">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
