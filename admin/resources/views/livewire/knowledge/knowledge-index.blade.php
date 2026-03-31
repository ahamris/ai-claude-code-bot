<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('bots.edit', $bot) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar {{ $bot->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Knowledge - {{ $bot->name }}</h1>
        </div>
        <a href="{{ route('bots.knowledge.create', $bot) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nieuw Item
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="divide-y divide-gray-200">
            @forelse($items as $item)
                <div class="p-5">
                    @if($editingId === $item->id)
                        {{-- Inline edit --}}
                        <div class="space-y-3">
                            <input type="text" wire:model="editTopic"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium">
                            <textarea wire:model="editContent" rows="6"
                                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"></textarea>
                            <div class="flex items-center gap-2">
                                <button wire:click="saveEdit" class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Opslaan</button>
                                <button wire:click="cancelEdit" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Annuleren</button>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-medium text-gray-900">{{ $item->topic }}</h3>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-3">{{ $item->content }}</p>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $item->source === 'manual' ? 'bg-blue-100 text-blue-700' : ($item->source === 'upload' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700') }}">
                                        {{ $item->source }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $item->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 ml-4">
                                <button wire:click="startEdit({{ $item->id }})" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete({{ $item->id }})" wire:confirm="Weet je zeker dat je dit item wilt verwijderen?" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <p>Nog geen knowledge items voor deze bot.</p>
                    <a href="{{ route('bots.knowledge.create', $bot) }}" class="text-indigo-600 hover:text-indigo-700 font-medium mt-2 inline-block">Maak het eerste item aan</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
