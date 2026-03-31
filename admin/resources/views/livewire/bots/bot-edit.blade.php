<div>
    <div class="mb-6">
        <a href="{{ route('bots.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar overzicht</a>
        <div class="flex items-center justify-between mt-2">
            <h1 class="text-2xl font-bold text-gray-900">{{ $bot->name }} bewerken</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('bots.knowledge', $bot) }}" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Knowledge</a>
                <a href="{{ route('bots.conversations', $bot) }}" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Gesprekken</a>
                <a href="{{ route('bots.monitoring', $bot) }}" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Monitoring</a>
                <a href="{{ route('bots.logs', $bot) }}" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Logs</a>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6">
            @foreach(['algemeen' => 'Algemeen', 'ai' => 'AI Config', 'monitoring' => 'Monitoring', 'ssh' => 'SSH Hosts', 'plugins' => 'Plugins'] as $tab => $label)
                <button wire:click="setTab('{{ $tab }}')"
                        class="pb-3 text-sm font-medium border-b-2 transition {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Tab: Algemeen --}}
        @if($activeTab === 'algemeen')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Algemene Instellingen</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" value="{{ $slug }}" class="w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm text-sm" disabled>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telegram Token <span class="text-gray-400 font-normal">(laat leeg om niet te wijzigen)</span></label>
                        <input type="password" wire:model="telegram_token" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Nieuw token invoeren...">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chat ID's <span class="text-gray-400 font-normal">(komma-gescheiden)</span></label>
                        <input type="text" wire:model="chat_ids_input" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">System Prompt</label>
                        <textarea wire:model="system_prompt" rows="12" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"></textarea>
                        @error('system_prompt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">Bot Actief</span>
                        </label>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tab: AI Config --}}
        @if($activeTab === 'ai')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">AI Configuratie</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Turns</label>
                        <input type="number" wire:model="ai_max_turns" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('ai_max_turns') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Timeout (seconden)</label>
                        <input type="number" wire:model="ai_timeout" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('ai_timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Working Directory</label>
                        <input type="text" wire:model="ai_working_dir" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="/opt/ai-bot/workspace">
                    </div>
                </div>

                <hr class="my-6">

                <h3 class="text-md font-semibold text-gray-900 mb-4">Memory</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="memory_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">Memory Ingeschakeld</span>
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="memory_auto_learn" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">Auto Learn</span>
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Directory</label>
                        <input type="text" wire:model="memory_data_dir" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="/opt/ai-bot/data/bot-slug">
                    </div>
                </div>

                <hr class="my-6">

                <h3 class="text-md font-semibold text-gray-900 mb-4">Formatter</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                        <select wire:model="formatter_mode" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="plain">Plain</option>
                            <option value="html">HTML</option>
                            <option value="markdown">Markdown</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="formatter_strip_markdown" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">Strip Markdown</span>
                        </label>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tab: Monitoring --}}
        @if($activeTab === 'monitoring')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Monitoring Instellingen</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="monitoring_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700">Monitoring Ingeschakeld</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Health Check Interval (s)</label>
                        <input type="number" wire:model="health_check_interval" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Security Scan Interval (s)</label>
                        <input type="number" wire:model="security_scan_interval" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                <hr class="my-6">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-md font-semibold text-gray-900">Monitoring Hosts</h3>
                    <button type="button" wire:click="addMonitoringHost" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Toevoegen
                    </button>
                </div>

                @foreach($monitoringHosts as $index => $host)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3 p-3 bg-gray-50 rounded-lg">
                        <input type="text" wire:model="monitoringHosts.{{ $index }}.group_name" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Groep">
                        <input type="text" wire:model="monitoringHosts.{{ $index }}.host_name" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Hostname">
                        <input type="text" wire:model="monitoringHosts.{{ $index }}.address" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="IP/Adres">
                        <div class="flex items-center">
                            <button type="button" wire:click="removeMonitoringHost({{ $index }})" class="p-1.5 text-red-500 hover:bg-red-50 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                @if(empty($monitoringHosts))
                    <p class="text-sm text-gray-500">Geen monitoring hosts geconfigureerd.</p>
                @endif
            </div>
        @endif

        {{-- Tab: SSH Hosts --}}
        @if($activeTab === 'ssh')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">SSH Hosts</h2>
                    <button type="button" wire:click="addSshHost" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Toevoegen
                    </button>
                </div>

                @foreach($sshHosts as $index => $host)
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-3 p-3 bg-gray-50 rounded-lg">
                        <input type="text" wire:model="sshHosts.{{ $index }}.name" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Naam">
                        <input type="text" wire:model="sshHosts.{{ $index }}.host" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Host">
                        <input type="text" wire:model="sshHosts.{{ $index }}.ssh_user" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="User">
                        <input type="text" wire:model="sshHosts.{{ $index }}.jump_host" class="rounded-lg border-gray-300 shadow-sm text-sm" placeholder="Jump Host (optioneel)">
                        <div class="flex items-center">
                            <button type="button" wire:click="removeSshHost({{ $index }})" class="p-1.5 text-red-500 hover:bg-red-50 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                @if(empty($sshHosts))
                    <p class="text-sm text-gray-500">Geen SSH hosts geconfigureerd.</p>
                @endif
            </div>
        @endif

        {{-- Tab: Plugins --}}
        @if($activeTab === 'plugins')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Plugins</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($availablePlugins as $plugin)
                        <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition border {{ in_array($plugin, $selectedPlugins) ? 'border-indigo-300 bg-indigo-50' : 'border-transparent' }}">
                            <input type="checkbox" wire:model="selectedPlugins" value="{{ $plugin }}"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700">{{ str_replace('_', ' ', ucfirst($plugin)) }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('bots.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuleren
            </a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                Opslaan & Herstarten
            </button>
        </div>
    </form>
</div>
