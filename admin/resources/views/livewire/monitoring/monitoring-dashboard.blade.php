<div wire:poll.10s>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:link href="{{ route('bots.edit', $bot) }}" class="text-sm text-zinc-500">
                <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar {{ $bot->name }}
            </flux:link>
            <flux:heading size="xl" class="mt-2">
                <i class="fa-solid fa-chart-line mr-2 text-indigo-500"></i> Monitoring - {{ $bot->name }}
            </flux:heading>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5 text-xs text-zinc-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Auto-refresh (10s)
            </div>
        </div>
    </div>

    @if(!$bot->monitoring_enabled)
        <flux:card class="border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <div class="text-center py-6">
                <i class="fa-solid fa-triangle-exclamation text-3xl text-amber-500 mb-3 block"></i>
                <flux:heading size="base">Monitoring is uitgeschakeld voor deze bot.</flux:heading>
                <flux:link href="{{ route('bots.edit', $bot) }}" class="mt-2 text-sm">
                    Inschakelen in instellingen
                </flux:link>
            </div>
        </flux:card>
    @else
        {{-- Host status cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @forelse($this->hostStatuses as $status)
                <flux:card class="!p-5 {{ $status['reachable'] ? 'border-emerald-200 dark:border-emerald-800' : 'border-rose-200 dark:border-rose-800' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            @if($status['reachable'])
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                            @else
                                <i class="fa-solid fa-circle-xmark text-rose-500"></i>
                            @endif
                            <flux:heading size="base">{{ $status['host']->host_name }}</flux:heading>
                        </div>
                        <flux:badge color="zinc" size="sm">{{ $status['host']->group_name }}</flux:badge>
                    </div>

                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">
                                <i class="fa-solid fa-network-wired mr-1 w-4 text-center"></i> Adres
                            </span>
                            <span class="font-mono text-xs">{{ $status['host']->address }}</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">
                                <i class="fa-solid fa-signal mr-1 w-4 text-center"></i> Status
                            </span>
                            @if($status['reachable'])
                                <flux:badge color="green" size="sm">Bereikbaar</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">Onbereikbaar</flux:badge>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">
                                <i class="fa-solid fa-stopwatch mr-1 w-4 text-center"></i> Latency
                            </span>
                            <span class="font-medium">{{ number_format($status['latency'], 1) }} ms</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">
                                <i class="fa-solid fa-chart-line mr-1 w-4 text-center"></i> Uptime (24u)
                            </span>
                            <span class="font-medium {{ $status['uptime_24h'] >= 99 ? 'text-emerald-600' : ($status['uptime_24h'] >= 95 ? 'text-amber-600' : 'text-rose-600') }}">
                                {{ $status['uptime_24h'] }}%
                            </span>
                        </div>

                        {{-- Uptime bar --}}
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 mt-1">
                            <div class="h-1.5 rounded-full transition-all {{ $status['uptime_24h'] >= 99 ? 'bg-emerald-500' : ($status['uptime_24h'] >= 95 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                 style="width: {{ $status['uptime_24h'] }}%"></div>
                        </div>

                        <div class="text-xs text-zinc-400 mt-1">
                            <i class="fa-solid fa-clock mr-1"></i>
                            Laatste check: {{ $status['last_checked']?->diffForHumans() ?? 'Nooit' }}
                        </div>
                    </div>
                </flux:card>
            @empty
                <div class="col-span-full">
                    <flux:card class="text-center py-8">
                        <i class="fa-solid fa-server text-3xl mb-2 block text-zinc-300"></i>
                        <p class="text-zinc-500">Geen monitoring hosts geconfigureerd.</p>
                        <flux:link href="{{ route('bots.edit', $bot) }}" class="mt-2">
                            Hosts toevoegen
                        </flux:link>
                    </flux:card>
                </div>
            @endforelse
        </div>

        {{-- Uptime chart --}}
        @if(count($this->hostStatuses) > 0)
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">
                    <i class="fa-solid fa-chart-bar mr-2 text-indigo-500"></i> Uptime Percentage per Host
                </flux:heading>
                <div class="space-y-3">
                    @foreach($this->hostStatuses as $status)
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium w-32 truncate">{{ $status['host']->host_name }}</span>
                            <div class="flex-1 bg-zinc-100 dark:bg-zinc-800 rounded-full h-6 relative overflow-hidden">
                                <div class="h-6 rounded-full flex items-center justify-end pr-2 text-xs font-medium text-white transition-all
                                    {{ $status['uptime_24h'] >= 99 ? 'bg-emerald-500' : ($status['uptime_24h'] >= 95 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                     style="width: {{ max($status['uptime_24h'], 5) }}%">
                                    {{ $status['uptime_24h'] }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            {{-- Alert geschiedenis als timeline --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    <i class="fa-solid fa-bell mr-2 text-indigo-500"></i> Status Overzicht
                </flux:heading>

                <flux:timeline>
                    @foreach($this->hostStatuses as $status)
                        <flux:timeline.item>
                            <flux:timeline.indicator>
                                <div class="flex h-6 w-6 items-center justify-center rounded-full {{ $status['reachable'] ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-rose-100 dark:bg-rose-900/50' }}">
                                    @if($status['reachable'])
                                        <i class="fa-solid fa-circle-check text-xs text-emerald-600"></i>
                                    @else
                                        <i class="fa-solid fa-circle-xmark text-xs text-rose-600"></i>
                                    @endif
                                </div>
                            </flux:timeline.indicator>

                            <flux:timeline.content>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $status['host']->host_name }}</span>
                                    <span class="text-zinc-400">&middot;</span>
                                    <span class="text-sm text-zinc-500 font-mono">{{ $status['host']->address }}</span>
                                    @if($status['reachable'])
                                        <flux:badge color="green" size="sm">Online</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm">Offline</flux:badge>
                                    @endif
                                </div>
                                <p class="text-sm text-zinc-500 mt-0.5">
                                    Latency: {{ number_format($status['latency'], 1) }} ms &middot;
                                    Uptime: {{ $status['uptime_24h'] }}% &middot;
                                    {{ $status['last_checked']?->diffForHumans() ?? 'Nooit gecontroleerd' }}
                                </p>
                            </flux:timeline.content>
                        </flux:timeline.item>
                    @endforeach
                </flux:timeline>
            </flux:card>
        @endif
    @endif
</div>
