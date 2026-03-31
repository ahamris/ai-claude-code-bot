<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Bots</h1>
        <a href="{{ route('bots.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nieuwe Bot
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Naam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Gesprekken</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Knowledge</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($bots as $bot)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <a href="{{ route('bots.edit', $bot) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $bot->name }}</a>
                            <p class="text-sm text-gray-500">{{ $bot->slug }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $bot->process_status === 'active' ? 'bg-green-100 text-green-800' : ($bot->process_status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                <span class="w-1.5 h-1.5 rounded-full
                                    {{ $bot->process_status === 'active' ? 'bg-green-500' : ($bot->process_status === 'failed' ? 'bg-red-500' : 'bg-gray-400') }}"></span>
                                {{ $bot->process_status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $bot->conversations_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $bot->knowledge_items_count }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="startBot({{ $bot->id }})" wire:confirm="Bot starten?" class="p-1.5 text-green-600 hover:bg-green-50 rounded transition" title="Start">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <button wire:click="stopBot({{ $bot->id }})" wire:confirm="Bot stoppen?" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition" title="Stop">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                                </button>
                                <button wire:click="restartBot({{ $bot->id }})" wire:confirm="Bot herstarten?" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded transition" title="Herstart">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                                <a href="{{ route('bots.edit', $bot) }}" class="p-1.5 text-gray-600 hover:bg-gray-100 rounded transition" title="Bewerk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Nog geen bots aangemaakt.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
