<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('bots.edit', $bot) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar {{ $bot->name }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Logs - {{ $bot->name }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="severityFilter"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">Alle niveaus</option>
                <option value="ERROR">Error</option>
                <option value="WARNING">Warning</option>
                <option value="INFO">Info</option>
                <option value="DEBUG">Debug</option>
            </select>
            <select wire:model.live="lines"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="50">50 regels</option>
                <option value="100">100 regels</option>
                <option value="200">200 regels</option>
                <option value="500">500 regels</option>
            </select>
            <div class="flex items-center gap-1 text-xs text-gray-400">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Live (5s)
            </div>
        </div>
    </div>

    <div class="bg-gray-900 rounded-xl shadow-sm border border-gray-700 p-1">
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-700">
            <span class="text-xs text-gray-400 font-mono">journalctl -u {{ config('aibot.systemd_unit_prefix') }}{{ $bot->slug }}</span>
        </div>
        <pre class="p-4 text-sm text-green-400 font-mono overflow-x-auto max-h-[600px] overflow-y-auto whitespace-pre-wrap break-words">{{ $logs }}</pre>
    </div>
</div>
