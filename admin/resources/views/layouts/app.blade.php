<!DOCTYPE html>
<html lang="nl" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AI Bot Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/fontawesome/css/all.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <flux:brand href="{{ route('dashboard') }}" class="px-2">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
                        <i class="fa-solid fa-robot text-white text-sm"></i>
                    </div>
                    <span class="text-lg font-bold truncate">AI Bot Admin</span>
                </div>
            </flux:brand>

            <flux:navlist variant="outline">
                <flux:navlist.group>
                    <flux:navlist.item
                        href="{{ route('dashboard') }}"
                        :current="request()->routeIs('dashboard')"
                    >
                        <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                        Dashboard
                    </flux:navlist.item>

                    <flux:navlist.item
                        href="{{ route('bots.index') }}"
                        :current="request()->routeIs('bots.index') || request()->routeIs('bots.create')"
                    >
                        <i class="fa-solid fa-robot w-5 text-center"></i>
                        Bots
                        <flux:badge size="sm" class="ml-auto">{{ \App\Models\Bot::count() }}</flux:badge>
                    </flux:navlist.item>
                </flux:navlist.group>

                @php $sidebarBots = \App\Models\Bot::orderBy('name')->get(); @endphp
                @foreach($sidebarBots as $sidebarBot)
                    <flux:navlist.group heading="{{ $sidebarBot->name }}" expandable :expanded="request()->is('*bots/' . $sidebarBot->id . '*')">
                        <flux:navlist.item
                            href="{{ route('bots.edit', $sidebarBot) }}"
                            :current="request()->routeIs('bots.edit') && request()->route('bot')?->id === $sidebarBot->id"
                        >
                            <i class="fa-solid fa-gear w-5 text-center text-xs"></i>
                            Instellingen
                            @if($sidebarBot->is_active)
                                <span class="ml-auto inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                            @else
                                <span class="ml-auto inline-block w-2 h-2 rounded-full bg-zinc-400"></span>
                            @endif
                        </flux:navlist.item>

                        <flux:navlist.item
                            href="{{ route('bots.knowledge', $sidebarBot) }}"
                            :current="request()->routeIs('bots.knowledge*') && request()->route('bot')?->id === $sidebarBot->id"
                        >
                            <i class="fa-solid fa-brain w-5 text-center text-xs"></i>
                            Knowledge
                        </flux:navlist.item>

                        <flux:navlist.item
                            href="{{ route('bots.conversations', $sidebarBot) }}"
                            :current="request()->routeIs('bots.conversations') && request()->route('bot')?->id === $sidebarBot->id"
                        >
                            <i class="fa-solid fa-comments w-5 text-center text-xs"></i>
                            Gesprekken
                        </flux:navlist.item>

                        <flux:navlist.item
                            href="{{ route('bots.monitoring', $sidebarBot) }}"
                            :current="request()->routeIs('bots.monitoring') && request()->route('bot')?->id === $sidebarBot->id"
                        >
                            <i class="fa-solid fa-chart-line w-5 text-center text-xs"></i>
                            Monitoring
                        </flux:navlist.item>

                        <flux:navlist.item
                            href="{{ route('bots.logs', $sidebarBot) }}"
                            :current="request()->routeIs('bots.logs') && request()->route('bot')?->id === $sidebarBot->id"
                        >
                            <i class="fa-solid fa-scroll w-5 text-center text-xs"></i>
                            Logs
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endforeach
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item href="{{ route('profile') }}">
                    <i class="fa-solid fa-gear w-5 text-center"></i>
                    Instellingen
                </flux:navlist.item>
            </flux:navlist>

            <flux:dropdown position="top" align="start">
                <flux:profile
                    :name="auth()->user()->name ?? 'Admin'"
                    :initials="substr(auth()->user()->name ?? 'A', 0, 1)"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="min-w-[200px]">
                    <flux:menu.item href="{{ route('profile') }}" icon="user">Profiel</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item
                        icon="arrow-right-start-on-rectangle"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    >
                        Uitloggen
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:profile
                :initials="substr(auth()->user()->name ?? 'A', 0, 1)"
                icon-trailing="chevron-down"
            />
        </flux:header>

        <flux:main>
            @if (session()->has('message'))
                <div class="mb-6">
                    <flux:callout variant="success" icon="check-circle" dismissible>
                        {{ session('message') }}
                    </flux:callout>
                </div>
            @endif

            {{ $slot }}
        </flux:main>
    </body>
</html>
