<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('bots.edit', $bot) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar {{ $bot->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Gesprekken - {{ $bot->name }}</h1>
        </div>
        <div>
            <input type="date" wire:model.live="dateFilter"
                   class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Gesprekken lijst --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="divide-y divide-gray-200">
                @forelse($conversations as $conversation)
                    <button wire:click="selectConversation({{ $conversation->id }})"
                            class="w-full p-4 text-left hover:bg-gray-50 transition {{ $selectedId === $conversation->id ? 'bg-indigo-50 border-l-4 border-indigo-500' : '' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400">{{ $conversation->created_at?->format('d-m-Y H:i') }}</span>
                            @if($conversation->telegram_user_id)
                                <span class="text-xs text-gray-400">User: {{ $conversation->telegram_user_id }}</span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-900 truncate">{{ Str::limit($conversation->user_message, 100) }}</p>
                        <p class="text-sm text-gray-500 truncate mt-0.5">{{ Str::limit($conversation->assistant_message, 100) }}</p>
                    </button>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        Geen gesprekken gevonden.
                    </div>
                @endforelse
            </div>

            @if($conversations->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>

        {{-- Detail view --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if($selected)
                <div class="space-y-4">
                    <div class="text-xs text-gray-400">{{ $selected->created_at?->format('d-m-Y H:i:s') }}</div>

                    <div class="p-4 bg-blue-50 rounded-lg">
                        <div class="text-xs font-semibold text-blue-600 mb-1">Gebruiker</div>
                        <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $selected->user_message }}</div>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-xs font-semibold text-gray-600 mb-1">Assistant</div>
                        <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $selected->assistant_message }}</div>
                    </div>
                </div>
            @else
                <div class="h-full flex items-center justify-center text-gray-400 text-sm">
                    Selecteer een gesprek om de details te bekijken.
                </div>
            @endif
        </div>
    </div>
</div>
