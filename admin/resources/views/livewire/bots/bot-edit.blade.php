<div>
    <div class="mb-6">
        <flux:link href="{{ route('bots.index') }}" class="text-sm text-zinc-500">
            <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar overzicht
        </flux:link>
        <div class="flex items-center justify-between mt-2">
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $bot->name }} bewerken</flux:heading>
                @if($bot->is_active)
                    <flux:badge color="green" size="sm">
                        <i class="fa-solid fa-circle-check mr-1"></i> Actief
                    </flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">
                        <i class="fa-solid fa-circle-minus mr-1"></i> Inactief
                    </flux:badge>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" href="{{ route('bots.knowledge', $bot) }}">
                    <i class="fa-solid fa-brain mr-1"></i> Knowledge
                </flux:button>
                <flux:button variant="ghost" size="sm" href="{{ route('bots.conversations', $bot) }}">
                    <i class="fa-solid fa-comments mr-1"></i> Gesprekken
                </flux:button>
                <flux:button variant="ghost" size="sm" href="{{ route('bots.monitoring', $bot) }}">
                    <i class="fa-solid fa-chart-line mr-1"></i> Monitoring
                </flux:button>
                <flux:button variant="ghost" size="sm" href="{{ route('bots.logs', $bot) }}">
                    <i class="fa-solid fa-scroll mr-1"></i> Logs
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <flux:tab.group>
        <flux:tabs wire:model="activeTab">
            <flux:tab name="algemeen">
                <i class="fa-solid fa-gear mr-1"></i> Algemeen
            </flux:tab>
            <flux:tab name="ai">
                <i class="fa-solid fa-brain mr-1"></i> AI Config
            </flux:tab>
            <flux:tab name="monitoring">
                <i class="fa-solid fa-chart-line mr-1"></i> Monitoring
            </flux:tab>
            <flux:tab name="ssh">
                <i class="fa-solid fa-server mr-1"></i> SSH Hosts
            </flux:tab>
            <flux:tab name="plugins">
                <i class="fa-solid fa-plug mr-1"></i> Plugins
            </flux:tab>
        </flux:tabs>

        <form wire:submit="save" class="mt-6 space-y-6">
            {{-- Tab: Algemeen --}}
            <flux:tab.panel name="algemeen">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Algemene Instellingen</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="name"
                            label="Naam"
                            required
                        />

                        <flux:input
                            value="{{ $slug }}"
                            label="Slug"
                            disabled
                        />

                        <div class="md:col-span-2">
                            <flux:input
                                wire:model="telegram_token"
                                type="password"
                                label="Telegram Token"
                                placeholder="Nieuw token invoeren..."
                                description="Laat leeg om niet te wijzigen"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <flux:input
                                wire:model="chat_ids_input"
                                label="Chat ID's"
                                description="Komma-gescheiden"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <flux:textarea
                                wire:model="system_prompt"
                                label="System Prompt"
                                rows="12"
                                class="font-mono"
                                required
                            />
                        </div>

                        <div>
                            <flux:checkbox
                                wire:model="is_active"
                                label="Bot Actief"
                                description="Schakel de bot in of uit"
                            />
                        </div>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Tab: AI Config --}}
            <flux:tab.panel name="ai">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">AI Configuratie</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input
                            wire:model="ai_max_turns"
                            type="number"
                            label="Max Turns"
                        />

                        <flux:input
                            wire:model="ai_timeout"
                            type="number"
                            label="Timeout (seconden)"
                        />

                        <div class="md:col-span-2">
                            <flux:input
                                wire:model="ai_working_dir"
                                label="Working Directory"
                                placeholder="/opt/ai-bot/workspace"
                            />
                        </div>
                    </div>

                    <flux:separator class="my-6" />

                    <flux:heading size="base" class="mb-4">
                        <i class="fa-solid fa-brain mr-1 text-indigo-500"></i> Memory
                    </flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:checkbox
                            wire:model="memory_enabled"
                            label="Memory Ingeschakeld"
                        />

                        <flux:checkbox
                            wire:model="memory_auto_learn"
                            label="Auto Learn"
                        />

                        <div class="md:col-span-2">
                            <flux:input
                                wire:model="memory_data_dir"
                                label="Data Directory"
                                placeholder="/opt/ai-bot/data/bot-slug"
                            />
                        </div>
                    </div>

                    <flux:separator class="my-6" />

                    <flux:heading size="base" class="mb-4">
                        <i class="fa-solid fa-code mr-1 text-indigo-500"></i> Formatter
                    </flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model="formatter_mode" label="Mode">
                            <flux:select.option value="plain">Plain</flux:select.option>
                            <flux:select.option value="html">HTML</flux:select.option>
                            <flux:select.option value="markdown">Markdown</flux:select.option>
                        </flux:select>

                        <div class="flex items-end pb-2">
                            <flux:checkbox
                                wire:model="formatter_strip_markdown"
                                label="Strip Markdown"
                            />
                        </div>
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Tab: Monitoring --}}
            <flux:tab.panel name="monitoring">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Monitoring Instellingen</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <flux:checkbox
                            wire:model="monitoring_enabled"
                            label="Monitoring Ingeschakeld"
                        />

                        <flux:input
                            wire:model="health_check_interval"
                            type="number"
                            label="Health Check Interval (s)"
                        />

                        <flux:input
                            wire:model="security_scan_interval"
                            type="number"
                            label="Security Scan Interval (s)"
                        />
                    </div>

                    <flux:separator class="my-6" />

                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="base">
                            <i class="fa-solid fa-server mr-1 text-indigo-500"></i> Monitoring Hosts
                        </flux:heading>
                        <flux:button variant="ghost" size="sm" wire:click="addMonitoringHost" type="button">
                            <i class="fa-solid fa-plus mr-1"></i> Toevoegen
                        </flux:button>
                    </div>

                    <div class="space-y-3">
                        @foreach($monitoringHosts as $index => $host)
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                <flux:input
                                    wire:model="monitoringHosts.{{ $index }}.group_name"
                                    placeholder="Groep"
                                    size="sm"
                                />
                                <flux:input
                                    wire:model="monitoringHosts.{{ $index }}.host_name"
                                    placeholder="Hostname"
                                    size="sm"
                                />
                                <flux:input
                                    wire:model="monitoringHosts.{{ $index }}.address"
                                    placeholder="IP/Adres"
                                    size="sm"
                                />
                                <div class="flex items-center">
                                    <flux:button variant="ghost" size="sm" wire:click="removeMonitoringHost({{ $index }})" type="button">
                                        <i class="fa-solid fa-trash text-rose-500"></i>
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach

                        @if(empty($monitoringHosts))
                            <div class="text-sm text-zinc-500 py-4 text-center">
                                Geen monitoring hosts geconfigureerd.
                            </div>
                        @endif
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Tab: SSH Hosts --}}
            <flux:tab.panel name="ssh">
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">
                            <i class="fa-solid fa-server mr-2 text-indigo-500"></i> SSH Hosts
                        </flux:heading>
                        <flux:button variant="ghost" size="sm" wire:click="addSshHost" type="button">
                            <i class="fa-solid fa-plus mr-1"></i> Toevoegen
                        </flux:button>
                    </div>

                    <div class="space-y-3">
                        @foreach($sshHosts as $index => $host)
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                <flux:input
                                    wire:model="sshHosts.{{ $index }}.name"
                                    placeholder="Naam"
                                    size="sm"
                                />
                                <flux:input
                                    wire:model="sshHosts.{{ $index }}.host"
                                    placeholder="Host"
                                    size="sm"
                                />
                                <flux:input
                                    wire:model="sshHosts.{{ $index }}.ssh_user"
                                    placeholder="User"
                                    size="sm"
                                />
                                <flux:input
                                    wire:model="sshHosts.{{ $index }}.jump_host"
                                    placeholder="Jump Host (optioneel)"
                                    size="sm"
                                />
                                <div class="flex items-center">
                                    <flux:button variant="ghost" size="sm" wire:click="removeSshHost({{ $index }})" type="button">
                                        <i class="fa-solid fa-trash text-rose-500"></i>
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach

                        @if(empty($sshHosts))
                            <div class="text-sm text-zinc-500 py-4 text-center">
                                <i class="fa-solid fa-network-wired text-zinc-300 text-xl mb-2 block"></i>
                                Geen SSH hosts geconfigureerd.
                            </div>
                        @endif
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Tab: Plugins --}}
            <flux:tab.panel name="plugins">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">
                        <i class="fa-solid fa-plug mr-2 text-indigo-500"></i> Plugins
                    </flux:heading>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($availablePlugins as $plugin)
                            <label class="flex items-center gap-3 p-4 rounded-lg cursor-pointer transition border
                                {{ in_array($plugin, $selectedPlugins) ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-600 dark:bg-indigo-900/20' : 'border-zinc-200 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800' }}">
                                <flux:checkbox wire:model="selectedPlugins" value="{{ $plugin }}" />
                                <div>
                                    <span class="text-sm font-medium">{{ str_replace('_', ' ', ucfirst($plugin)) }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </flux:card>
            </flux:tab.panel>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('bots.index') }}">
                    Annuleren
                </flux:button>
                <flux:button variant="primary" type="submit">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Opslaan & Herstarten
                </flux:button>
            </div>
        </form>
    </flux:tab.group>
</div>
