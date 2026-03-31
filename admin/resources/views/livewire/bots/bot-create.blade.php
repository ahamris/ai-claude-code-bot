<div>
    <div class="mb-6">
        <a href="{{ route('bots.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar overzicht</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nieuwe Bot Aanmaken</h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basis informatie --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basis Informatie</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
                    <input type="text" id="name" wire:model.live="name"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           placeholder="DevOps Bot">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" id="slug" wire:model="slug"
                           class="w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm text-sm" readonly>
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="telegram_token" class="block text-sm font-medium text-gray-700 mb-1">Telegram Token</label>
                    <input type="password" id="telegram_token" wire:model="telegram_token"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
                    @error('telegram_token') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="chat_ids" class="block text-sm font-medium text-gray-700 mb-1">Chat ID's <span class="text-gray-400 font-normal">(komma-gescheiden)</span></label>
                    <input type="text" id="chat_ids" wire:model="chat_ids_input"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           placeholder="123456789, 987654321">
                </div>

                <div class="md:col-span-2">
                    <label for="system_prompt" class="block text-sm font-medium text-gray-700 mb-1">System Prompt</label>
                    <textarea id="system_prompt" wire:model="system_prompt" rows="8"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                              placeholder="Je bent een DevOps assistant..."></textarea>
                    @error('system_prompt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Monitoring Hosts --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Monitoring Hosts</h2>
                <button type="button" wire:click="addMonitoringHost"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Host Toevoegen
                </button>
            </div>

            @foreach($monitoringHosts as $index => $host)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3 p-3 bg-gray-50 rounded-lg">
                    <div>
                        <input type="text" wire:model="monitoringHosts.{{ $index }}.group_name"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Groep">
                    </div>
                    <div>
                        <input type="text" wire:model="monitoringHosts.{{ $index }}.host_name"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Hostname">
                    </div>
                    <div>
                        <input type="text" wire:model="monitoringHosts.{{ $index }}.address"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm" placeholder="IP/Hostname">
                    </div>
                    <div class="flex items-center">
                        <button type="button" wire:click="removeMonitoringHost({{ $index }})"
                                class="p-1.5 text-red-500 hover:bg-red-50 rounded transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach

            @if(empty($monitoringHosts))
                <p class="text-sm text-gray-500">Nog geen monitoring hosts toegevoegd.</p>
            @endif
        </div>

        {{-- Plugins --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Plugins</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($availablePlugins as $plugin)
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                        <input type="checkbox" wire:model="selectedPlugins" value="{{ $plugin }}"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">{{ str_replace('_', ' ', ucfirst($plugin)) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('bots.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuleren
            </a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                Bot Aanmaken
            </button>
        </div>
    </form>
</div>
