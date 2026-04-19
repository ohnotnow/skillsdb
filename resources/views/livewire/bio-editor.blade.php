<div>
    <flux:button wire:click="openModal" icon="user-circle" variant="subtle">
        About Me
    </flux:button>

    <flux:modal wire:model="showModal" variant="flyout" class="w-[32rem]">
        <form wire:submit="save">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">About Me</flux:heading>
                    <flux:text class="mt-2">
                        Jot down the little things about yourself that don't fit a formal skill — hobbies, side
                        projects, things you used to do, topics you'd love to get involved in. It helps people
                        find common ground.
                    </flux:text>
                </div>

                <flux:callout icon="information-circle" color="blue">
                    <flux:callout.heading>How this is used</flux:callout.heading>
                    <flux:callout.text>
                        Your bio is private — other team members and admins won't see it here. It is shared with
                        the Skills Coach and Team Coach AI assistants to help spark informal connections
                        (e.g. "I see you both tinker with Raspberry Pis — have a chat!"). That means the text
                        is sent to an external AI API when those coaches are used.
                    </flux:callout.text>
                </flux:callout>

                <flux:textarea
                    wire:model="bio"
                    label="Your bio"
                    rows="8"
                    placeholder="e.g. I mess about with Raspberry Pis and home automation, used to be a Solaris admin back in the day, keen to get involved in anything Kubernetes-related..."
                />

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeModal">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
