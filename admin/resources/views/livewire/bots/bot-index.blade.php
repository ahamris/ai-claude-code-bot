<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Bots</flux:heading>
        <flux:button variant="primary" href="{{ route('bots.create') }}">
            <i class="fa-solid fa-plus mr-1"></i> Nieuwe Bot
        </flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Bot</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Knowledge</flux:table.column>
                <flux:table.column>Gesprekken</flux:table.column>
                <flux:table.column>Acties</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($bots as $bot)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $bot->is_active ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                    <i class="fa-solid fa-robot {{ $bot->is_active ? 'text-emerald-500' : 'text-zinc-400' }}"></i>
                                </div>
                                <div>
                                    <flux:link href="{{ route('bots.edit', $bot) }}" class="font-medium">{{ $bot->name }}</flux:link>
                                    <div class="text-xs text-zinc-500">{{ $bot->slug }}</div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($bot->process_status === 'active')
                                <flux:badge color="green" size="sm" inset="top bottom">
                                    <i class="fa-solid fa-circle-check mr-1"></i> Actief
                                </flux:badge>
                            @elseif($bot->process_status === 'failed')
                                <flux:badge color="red" size="sm" inset="top bottom">
                                    <i class="fa-solid fa-circle-xmark mr-1"></i> Gefaald
                                </flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" inset="top bottom">
                                    <i class="fa-solid fa-circle-minus mr-1"></i> Inactief
                                </flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-brain text-xs text-zinc-400"></i>
                                <span class="text-sm">{{ $bot->knowledge_items_count }}</span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-comments text-xs text-zinc-400"></i>
                                <span class="text-sm">{{ $bot->conversations_count }}</span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:tooltip content="Start">
                                    <flux:button variant="ghost" size="sm" wire:click="startBot({{ $bot->id }})" wire:confirm="Bot starten?">
                                        <i class="fa-solid fa-play text-emerald-600"></i>
                                    </flux:button>
                                </flux:tooltip>

                                <flux:tooltip content="Stop">
                                    <flux:button variant="ghost" size="sm" wire:click="stopBot({{ $bot->id }})" wire:confirm="Bot stoppen?">
                                        <i class="fa-solid fa-stop text-rose-600"></i>
                                    </flux:button>
                                </flux:tooltip>

                                <flux:tooltip content="Herstart">
                                    <flux:button variant="ghost" size="sm" wire:click="restartBot({{ $bot->id }})" wire:confirm="Bot herstarten?">
                                        <i class="fa-solid fa-rotate text-amber-600"></i>
                                    </flux:button>
                                </flux:tooltip>

                                <flux:separator vertical class="mx-1 h-5" />

                                <flux:tooltip content="Bewerken">
                                    <flux:button variant="ghost" size="sm" href="{{ route('bots.edit', $bot) }}">
                                        <i class="fa-solid fa-pen text-zinc-500"></i>
                                    </flux:button>
                                </flux:tooltip>

                                <flux:tooltip content="Knowledge">
                                    <flux:button variant="ghost" size="sm" href="{{ route('bots.knowledge', $bot) }}">
                                        <i class="fa-solid fa-brain text-zinc-500"></i>
                                    </flux:button>
                                </flux:tooltip>

                                <flux:tooltip content="Monitoring">
                                    <flux:button variant="ghost" size="sm" href="{{ route('bots.monitoring', $bot) }}">
                                        <i class="fa-solid fa-chart-line text-zinc-500"></i>
                                    </flux:button>
                                </flux:tooltip>

                                <flux:tooltip content="Logs">
                                    <flux:button variant="ghost" size="sm" href="{{ route('bots.logs', $bot) }}">
                                        <i class="fa-solid fa-scroll text-zinc-500"></i>
                                    </flux:button>
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <div class="text-zinc-500">
                                <i class="fa-solid fa-robot text-3xl mb-2 block text-zinc-300"></i>
                                <p>Nog geen bots aangemaakt.</p>
                                <flux:link href="{{ route('bots.create') }}" class="mt-2">Maak je eerste bot aan</flux:link>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
