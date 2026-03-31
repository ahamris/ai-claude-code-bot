<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:link href="{{ route('bots.edit', $bot) }}" class="text-sm text-zinc-500">
                <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar {{ $bot->name }}
            </flux:link>
            <flux:heading size="xl" class="mt-2">
                <i class="fa-solid fa-comments mr-2 text-indigo-500"></i> Gesprekken - {{ $bot->name }}
            </flux:heading>
        </div>
        <div class="flex items-center gap-3">
            <flux:input
                wire:model.live="dateFilter"
                type="date"
                placeholder="Filter op datum"
            />
        </div>
    </div>

    {{-- Gesprekken tabel --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Datum</flux:table.column>
                <flux:table.column>Gebruiker</flux:table.column>
                <flux:table.column>Bericht</flux:table.column>
                <flux:table.column>Antwoord</flux:table.column>
                <flux:table.column>Acties</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($conversations as $conversation)
                    <flux:table.row>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-500 whitespace-nowrap">{{ $conversation->created_at?->format('d-m-Y H:i') }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($conversation->telegram_user_id)
                                <flux:badge color="zinc" size="sm">
                                    <i class="fa-solid fa-user mr-1"></i> {{ $conversation->telegram_user_id }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm line-clamp-1 max-w-xs">{{ Str::limit($conversation->user_message, 80) }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm text-zinc-500 line-clamp-1 max-w-xs">{{ Str::limit($conversation->assistant_message, 80) }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:modal.trigger name="conversation-{{ $conversation->id }}">
                                <flux:button variant="ghost" size="sm">
                                    <i class="fa-solid fa-eye text-zinc-500"></i>
                                </flux:button>
                            </flux:modal.trigger>
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Modal voor volledig gesprek --}}
                    <flux:modal name="conversation-{{ $conversation->id }}" class="max-w-2xl">
                        <div class="space-y-4">
                            <flux:heading size="lg">Gesprek Details</flux:heading>
                            <flux:subheading>{{ $conversation->created_at?->format('d-m-Y H:i:s') }}</flux:subheading>

                            <div class="space-y-4 mt-4">
                                <div class="p-4 bg-sky-50 dark:bg-sky-900/20 rounded-lg border border-sky-200 dark:border-sky-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-user text-sky-600"></i>
                                        <span class="text-xs font-semibold text-sky-700 dark:text-sky-300">Gebruiker</span>
                                        @if($conversation->telegram_user_id)
                                            <flux:badge color="sky" size="sm">ID: {{ $conversation->telegram_user_id }}</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-sm whitespace-pre-wrap">{{ $conversation->user_message }}</div>
                                </div>

                                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-robot text-indigo-600"></i>
                                        <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Assistant</span>
                                    </div>
                                    <div class="text-sm whitespace-pre-wrap">{{ $conversation->assistant_message }}</div>
                                </div>
                            </div>
                        </div>
                    </flux:modal>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <div class="text-zinc-500">
                                <i class="fa-solid fa-comments text-3xl mb-2 block text-zinc-300"></i>
                                <p>Geen gesprekken gevonden.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($conversations->hasPages())
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $conversations->links() }}
            </div>
        @endif
    </flux:card>
</div>
