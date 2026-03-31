<div>
    <flux:heading size="xl" class="mb-6">Dashboard</flux:heading>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <flux:card class="!p-5">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>Actieve Bots</flux:subheading>
                    <flux:heading size="xl" class="mt-1">{{ $activeBots }}<span class="text-lg text-zinc-400">/{{ $totalBots }}</span></flux:heading>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                    <i class="fa-solid fa-robot text-xl text-indigo-500"></i>
                </div>
            </div>
        </flux:card>

        <flux:card class="!p-5">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>Knowledge Items</flux:subheading>
                    <flux:heading size="xl" class="mt-1">{{ $knowledgeCount }}</flux:heading>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                    <i class="fa-solid fa-brain text-xl text-emerald-500"></i>
                </div>
            </div>
        </flux:card>

        <flux:card class="!p-5">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>Gesprekken Vandaag</flux:subheading>
                    <flux:heading size="xl" class="mt-1">{{ $conversationsToday }}</flux:heading>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-900/30">
                    <i class="fa-solid fa-comments text-xl text-sky-500"></i>
                </div>
            </div>
        </flux:card>

        <flux:card class="!p-5">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>Alerts</flux:subheading>
                    <flux:heading size="xl" class="mt-1">{{ $totalBots }}</flux:heading>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30">
                    <i class="fa-solid fa-bell text-xl text-amber-500"></i>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Bot overzicht tabel --}}
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">Bots</flux:heading>
            <flux:button variant="primary" href="{{ route('bots.create') }}">
                <i class="fa-solid fa-plus mr-1"></i> Nieuwe Bot
            </flux:button>
        </div>

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
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $bot->knowledge_items_count }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $bot->conversations_count }}</span>
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

                                <flux:tooltip content="Bewerken">
                                    <flux:button variant="ghost" size="sm" href="{{ route('bots.edit', $bot) }}">
                                        <i class="fa-solid fa-pen text-zinc-500"></i>
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

    {{-- Recente gesprekken als timeline --}}
    @if($bots->count() > 0)
        <flux:card class="mt-6">
            <flux:heading size="lg" class="mb-4">Recente Activiteit</flux:heading>

            <flux:timeline>
                @foreach($bots->take(5) as $bot)
                    <flux:timeline.item>
                        <flux:timeline.indicator>
                            <div class="flex h-6 w-6 items-center justify-center rounded-full {{ $bot->process_status === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                <i class="fa-solid fa-robot text-xs {{ $bot->process_status === 'active' ? 'text-emerald-600' : 'text-zinc-400' }}"></i>
                            </div>
                        </flux:timeline.indicator>

                        <flux:timeline.content>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $bot->name }}</span>
                                @if($bot->process_status === 'active')
                                    <flux:badge color="green" size="sm">Actief</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ $bot->process_status }}</flux:badge>
                                @endif
                            </div>
                            <p class="text-sm text-zinc-500 mt-1">
                                {{ $bot->conversations_count }} gesprekken &middot; {{ $bot->knowledge_items_count }} knowledge items
                            </p>
                        </flux:timeline.content>
                    </flux:timeline.item>
                @endforeach
            </flux:timeline>
        </flux:card>
    @endif
</div>
