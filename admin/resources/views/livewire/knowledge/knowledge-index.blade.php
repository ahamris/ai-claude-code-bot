<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:link href="{{ route('bots.edit', $bot) }}" class="text-sm text-zinc-500">
                <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar {{ $bot->name }}
            </flux:link>
            <flux:heading size="xl" class="mt-2">
                <i class="fa-solid fa-brain mr-2 text-indigo-500"></i> Knowledge - {{ $bot->name }}
            </flux:heading>
        </div>
        <flux:button variant="primary" href="{{ route('bots.knowledge.create', $bot) }}">
            <i class="fa-solid fa-plus mr-1"></i> Nieuw Item
        </flux:button>
    </div>

    {{-- Search/Filter --}}
    <flux:card class="mb-4">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Zoeken in knowledge items..."
                    icon="magnifying-glass"
                />
            </div>
        </div>
    </flux:card>

    {{-- Knowledge tabel --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Topic</flux:table.column>
                <flux:table.column>Bron</flux:table.column>
                <flux:table.column>Datum</flux:table.column>
                <flux:table.column>Acties</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($items as $item)
                    <flux:table.row>
                        <flux:table.cell>
                            @if($editingId === $item->id)
                                <div class="space-y-3 py-2">
                                    <flux:input
                                        wire:model="editTopic"
                                        placeholder="Topic"
                                        size="sm"
                                    />
                                    <flux:textarea
                                        wire:model="editContent"
                                        rows="6"
                                        class="font-mono"
                                        size="sm"
                                    />
                                    <div class="flex items-center gap-2">
                                        <flux:button variant="primary" size="sm" wire:click="saveEdit">
                                            <i class="fa-solid fa-floppy-disk mr-1"></i> Opslaan
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" wire:click="cancelEdit">
                                            Annuleren
                                        </flux:button>
                                    </div>
                                </div>
                            @else
                                <div>
                                    <span class="font-medium">{{ $item->topic }}</span>
                                    <p class="text-sm text-zinc-500 mt-1 line-clamp-2">{{ $item->content }}</p>
                                </div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($item->source === 'manual')
                                <flux:badge color="blue" size="sm">
                                    <i class="fa-solid fa-pen mr-1"></i> Handmatig
                                </flux:badge>
                            @elseif($item->source === 'upload')
                                <flux:badge color="purple" size="sm">
                                    <i class="fa-solid fa-file-arrow-up mr-1"></i> Upload
                                </flux:badge>
                            @elseif($item->source === 'auto_learn')
                                <flux:badge color="amber" size="sm">
                                    <i class="fa-solid fa-brain mr-1"></i> Auto Learn
                                </flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ $item->source }}</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm text-zinc-500">{{ $item->updated_at->diffForHumans() }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($editingId !== $item->id)
                                <div class="flex items-center gap-1">
                                    <flux:tooltip content="Bewerken">
                                        <flux:button variant="ghost" size="sm" wire:click="startEdit({{ $item->id }})">
                                            <i class="fa-solid fa-pen text-zinc-500"></i>
                                        </flux:button>
                                    </flux:tooltip>

                                    <flux:tooltip content="Verwijderen">
                                        <flux:button variant="ghost" size="sm" wire:click="delete({{ $item->id }})" wire:confirm="Weet je zeker dat je dit item wilt verwijderen?">
                                            <i class="fa-solid fa-trash text-rose-500"></i>
                                        </flux:button>
                                    </flux:tooltip>
                                </div>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8">
                            <div class="text-zinc-500">
                                <i class="fa-solid fa-brain text-3xl mb-2 block text-zinc-300"></i>
                                <p>Nog geen knowledge items voor deze bot.</p>
                                <flux:link href="{{ route('bots.knowledge.create', $bot) }}" class="mt-2">
                                    Maak het eerste item aan
                                </flux:link>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
