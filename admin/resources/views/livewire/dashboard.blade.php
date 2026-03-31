<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Actieve Bots</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeBots }}<span class="text-lg text-gray-400">/{{ $totalBots }}</span></p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Knowledge Items</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $knowledgeCount }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Gesprekken Vandaag</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $conversationsToday }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Totaal Bots</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalBots }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Bot lijst --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Bots</h2>
            <a href="{{ route('bots.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nieuwe Bot
            </a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($bots as $bot)
                <div class="p-5 flex items-center justify-between hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg {{ $bot->is_active ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                            <span class="w-3 h-3 rounded-full {{ $bot->process_status === 'active' ? 'bg-green-500' : ($bot->process_status === 'failed' ? 'bg-red-500' : 'bg-gray-400') }}"></span>
                        </div>
                        <div>
                            <a href="{{ route('bots.edit', $bot) }}" class="font-medium text-gray-900 hover:text-indigo-600 transition">{{ $bot->name }}</a>
                            <p class="text-sm text-gray-500">{{ $bot->slug }} &middot; {{ $bot->knowledge_items_count }} knowledge items &middot; {{ $bot->conversations_count }} gesprekken</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bot->process_status === 'active' ? 'bg-green-100 text-green-800' : ($bot->process_status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ $bot->process_status }}
                        </span>
                        <a href="{{ route('bots.edit', $bot) }}" class="p-2 text-gray-400 hover:text-indigo-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <p>Nog geen bots aangemaakt.</p>
                    <a href="{{ route('bots.create') }}" class="text-indigo-600 hover:text-indigo-700 font-medium mt-2 inline-block">Maak je eerste bot aan</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
