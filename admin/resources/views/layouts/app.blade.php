<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AI Bot Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen flex">
            {{-- Sidebar --}}
            <aside class="w-64 bg-gray-900 text-gray-300 flex flex-col flex-shrink-0 min-h-screen">
                <div class="p-4 border-b border-gray-700">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-lg font-bold text-white">AI Bot Admin</span>
                    </a>
                </div>

                <nav class="flex-1 p-4 space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>

                    <a href="{{ route('bots.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('bots.*') ? 'bg-gray-800 text-white' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                        Bots
                        <span class="ml-auto bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded-full">
                            {{ \App\Models\Bot::count() }}
                        </span>
                    </a>

                    @php $bots = \App\Models\Bot::orderBy('name')->get(); @endphp
                    @foreach($bots as $sidebarBot)
                        <div class="ml-4 space-y-0.5">
                            <div class="px-3 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                {{ $sidebarBot->name }}
                            </div>
                            <a href="{{ route('bots.edit', $sidebarBot) }}"
                               class="flex items-center gap-2 px-3 py-1 rounded text-xs transition hover:bg-gray-800 hover:text-white">
                                <span class="w-1.5 h-1.5 rounded-full {{ $sidebarBot->is_active ? 'bg-green-400' : 'bg-gray-500' }}"></span>
                                Instellingen
                            </a>
                            <a href="{{ route('bots.knowledge', $sidebarBot) }}"
                               class="flex items-center gap-2 px-3 py-1 rounded text-xs transition hover:bg-gray-800 hover:text-white">
                                Knowledge
                            </a>
                            <a href="{{ route('bots.conversations', $sidebarBot) }}"
                               class="flex items-center gap-2 px-3 py-1 rounded text-xs transition hover:bg-gray-800 hover:text-white">
                                Gesprekken
                            </a>
                            <a href="{{ route('bots.monitoring', $sidebarBot) }}"
                               class="flex items-center gap-2 px-3 py-1 rounded text-xs transition hover:bg-gray-800 hover:text-white">
                                Monitoring
                            </a>
                            <a href="{{ route('bots.logs', $sidebarBot) }}"
                               class="flex items-center gap-2 px-3 py-1 rounded text-xs transition hover:bg-gray-800 hover:text-white">
                                Logs
                            </a>
                        </div>
                    @endforeach
                </nav>

                <div class="p-4 border-t border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-bold">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                        </div>
                        <a href="{{ route('profile') }}" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="flex-1 flex flex-col">
                <main class="flex-1 p-6">
                    @if (session()->has('message'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                            {{ session('message') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
