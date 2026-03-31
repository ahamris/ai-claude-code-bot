<div>
    <div class="mb-6">
        <a href="{{ route('bots.edit', $bot) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar {{ $bot->name }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Monitoring - {{ $bot->name }}</h1>
    </div>

    @if(!$bot->monitoring_enabled)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
            <svg class="mx-auto w-12 h-12 text-amber-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <p class="text-amber-800 font-medium">Monitoring is uitgeschakeld voor deze bot.</p>
            <a href="{{ route('bots.edit', $bot) }}" class="text-amber-600 hover:text-amber-700 text-sm mt-1 inline-block">Inschakelen in instellingen</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->hostStatuses as $status)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full {{ $status['reachable'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            <h3 class="font-medium text-gray-900">{{ $status['host']->host_name }}</h3>
                        </div>
                        <span class="text-xs text-gray-400">{{ $status['host']->group_name }}</span>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Adres</span>
                            <span class="text-gray-700 font-mono text-xs">{{ $status['host']->address }}</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Status</span>
                            <span class="{{ $status['reachable'] ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ $status['reachable'] ? 'Bereikbaar' : 'Onbereikbaar' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Latency</span>
                            <span class="text-gray-700">{{ number_format($status['latency'], 1) }} ms</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Uptime (24u)</span>
                            <span class="{{ $status['uptime_24h'] >= 99 ? 'text-green-600' : ($status['uptime_24h'] >= 95 ? 'text-amber-600' : 'text-red-600') }} font-medium">
                                {{ $status['uptime_24h'] }}%
                            </span>
                        </div>

                        {{-- Uptime bar --}}
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                            <div class="h-1.5 rounded-full {{ $status['uptime_24h'] >= 99 ? 'bg-green-500' : ($status['uptime_24h'] >= 95 ? 'bg-amber-500' : 'bg-red-500') }}"
                                 style="width: {{ $status['uptime_24h'] }}%"></div>
                        </div>

                        <div class="text-xs text-gray-400 mt-1">
                            Laatste check: {{ $status['last_checked']?->diffForHumans() ?? 'Nooit' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-gray-50 rounded-xl p-8 text-center text-gray-500">
                    <p>Geen monitoring hosts geconfigureerd.</p>
                    <a href="{{ route('bots.edit', $bot) }}" class="text-indigo-600 hover:text-indigo-700 font-medium mt-2 inline-block">Hosts toevoegen</a>
                </div>
            @endforelse
        </div>
    @endif
</div>
