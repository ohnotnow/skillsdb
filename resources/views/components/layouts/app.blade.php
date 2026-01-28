<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        @livewireStyles
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @auth
            <flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700 print:hidden">
                <flux:sidebar.header>
                    <flux:sidebar.brand
                        href="#"
                        logo="https://fluxui.dev/img/demo/logo.png"
                        logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                        name="{{  config('app.name') }}"
                    />
                    <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                </flux:sidebar.header>
                <flux:sidebar.nav>
                    <flux:sidebar.item icon="home" href="/" wire:navigate>My Skills</flux:sidebar.item>
                    @if (auth()->user()->isTeamManager())
                        <flux:sidebar.item icon="user-group" href="/admin/team-coach" wire:navigate>Team Coach</flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" href="{{ route('manager.training-requests') }}" wire:navigate>Training Requests</flux:sidebar.item>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <flux:separator class="my-2" />
                        <flux:sidebar.item icon="wrench-screwdriver" href="/admin/skills" wire:navigate>Manage Skills</flux:sidebar.item>
                        <flux:sidebar.item icon="share" href="/admin/skills/visualisation" wire:navigate>Skills Map</flux:sidebar.item>
                        <flux:sidebar.item icon="academic-cap" href="/admin/training" wire:navigate>Manage Courses</flux:sidebar.item>
                        <flux:sidebar.item icon="users" href="/admin/users" wire:navigate>Manage Users</flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" href="/admin/dashboard" wire:navigate>Skills Dashboard</flux:sidebar.item>
                        <flux:sidebar.item icon="key" href="/admin/api-tokens" wire:navigate>API Tokens</flux:sidebar.item>
                        <flux:sidebar.item icon="play" href="/play" wire:navigate>Play Space</flux:sidebar.item>
                    @endif
                </flux:sidebar.nav>
                <flux:sidebar.spacer />
                <flux:sidebar.nav>
                    <flux:sidebar.item tooltip="Logout" icon="arrow-right-start-on-rectangle">
                        <form method="post" action="{{ route('auth.logout') }}">
                            @csrf
                            <flux:button class="w-full" type="submit">
                                <span class="hidden sm:block">Logout</span>
                            </flux:button>
                        </form>
                    </flux:sidebar.item>
                </flux:sidebar.nav>
            </flux:sidebar>
        @endauth
        <flux:header class="lg:hidden print:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="start">

                <flux:menu>
                    <flux:menu.item icon="arrow-right-start-on-rectangle">
                        <form method="post" action="{{ route('auth.logout') }}">
                            @csrf
                            <flux:button type="submit">Logout</flux:button>
                        </form>
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        <flux:toast />
        @fluxScripts
        @stack('scripts')
    </body>
</html>
