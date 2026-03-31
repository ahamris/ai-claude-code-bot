<div wire:poll.5s>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:link href="{{ route('bots.edit', $bot) }}" class="text-sm text-zinc-500">
                <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar {{ $bot->name }}
            </flux:link>
            <flux:heading size="xl" class="mt-2">
                <i class="fa-solid fa-scroll mr-2 text-indigo-500"></i> Logs - {{ $bot->name }}
            </flux:heading>
        </div>
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="severityFilter" size="sm" class="w-40">
                <flux:select.option value="">Alle niveaus</flux:select.option>
                <flux:select.option value="ERROR">Error</flux:select.option>
                <flux:select.option value="WARNING">Warning</flux:select.option>
                <flux:select.option value="INFO">Info</flux:select.option>
                <flux:select.option value="DEBUG">Debug</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="lines" size="sm" class="w-32">
                <flux:select.option value="50">50 regels</flux:select.option>
                <flux:select.option value="100">100 regels</flux:select.option>
                <flux:select.option value="200">200 regels</flux:select.option>
                <flux:select.option value="500">500 regels</flux:select.option>
            </flux:select>

            <div class="flex items-center gap-1.5 text-xs text-zinc-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Live (5s)
            </div>
        </div>
    </div>

    <flux:card class="!p-0 overflow-hidden">
        {{-- Terminal header --}}
        <div class="flex items-center justify-between px-4 py-2.5 bg-zinc-800 dark:bg-zinc-900 border-b border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="text-xs text-zinc-400 font-mono ml-2">journalctl -u {{ config('aibot.systemd_unit_prefix') }}{{ $bot->slug }}</span>
            </div>
            <div class="flex items-center gap-2">
                @if($severityFilter)
                    <flux:badge color="indigo" size="sm">
                        Filter: {{ $severityFilter }}
                    </flux:badge>
                @endif
            </div>
        </div>

        {{-- Log output --}}
        <div class="bg-zinc-900 p-4 overflow-x-auto max-h-[650px] overflow-y-auto">
            @if($logs)
                @foreach(explode("\n", $logs) as $line)
                    @if(trim($line))
                        <div class="font-mono text-sm leading-relaxed whitespace-pre-wrap break-words
                            @if(stripos($line, 'ERROR') !== false)
                                text-rose-400
                            @elseif(stripos($line, 'WARNING') !== false || stripos($line, 'WARN') !== false)
                                text-amber-400
                            @elseif(stripos($line, 'INFO') !== false)
                                text-sky-400
                            @elseif(stripos($line, 'DEBUG') !== false)
                                text-zinc-500
                            @else
                                text-emerald-400
                            @endif
                        ">{{ $line }}</div>
                    @endif
                @endforeach
            @else
                <div class="text-center py-12 text-zinc-500">
                    <i class="fa-solid fa-scroll text-3xl mb-3 block text-zinc-600"></i>
                    <p class="font-mono text-sm">Geen logs beschikbaar.</p>
                </div>
            @endif
        </div>

        {{-- Legenda --}}
        <div class="flex items-center gap-4 px-4 py-2 bg-zinc-800 dark:bg-zinc-900 border-t border-zinc-700">
            <span class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                <span class="text-zinc-400">ERROR</span>
            </span>
            <span class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="text-zinc-400">WARNING</span>
            </span>
            <span class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                <span class="text-zinc-400">INFO</span>
            </span>
            <span class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-zinc-500"></span>
                <span class="text-zinc-400">DEBUG</span>
            </span>
        </div>
    </flux:card>
</div>
