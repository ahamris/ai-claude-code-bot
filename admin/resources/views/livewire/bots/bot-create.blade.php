<div>
    <div class="mb-6">
        <flux:link href="{{ route('bots.index') }}" class="text-sm text-zinc-500">
            <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar overzicht
        </flux:link>
        <flux:heading size="xl" class="mt-2">Nieuwe Bot Aanmaken</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basis informatie --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fa-solid fa-robot mr-2 text-indigo-500"></i> Basis Informatie
            </flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    wire:model.live="name"
                    label="Naam"
                    placeholder="DevOps Bot"
                    required
                />

                <flux:input
                    wire:model="slug"
                    label="Slug"
                    readonly
                    disabled
                    description="Wordt automatisch gegenereerd"
                />

                <div class="md:col-span-2">
                    <flux:input
                        wire:model="telegram_token"
                        type="password"
                        label="Telegram Token"
                        placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"
                        required
                    />
                </div>

                <div class="md:col-span-2">
                    <flux:input
                        wire:model="chat_ids_input"
                        label="Chat ID's"
                        placeholder="123456789, 987654321"
                        description="Komma-gescheiden lijst van Telegram chat ID's"
                    />
                </div>

                <div class="md:col-span-2">
                    <flux:textarea
                        wire:model="system_prompt"
                        label="System Prompt"
                        rows="8"
                        placeholder="Je bent een DevOps assistant..."
                        required
                        class="font-mono"
                    />
                </div>
            </div>
        </flux:card>

        {{-- Monitoring Hosts --}}
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">
                    <i class="fa-solid fa-server mr-2 text-indigo-500"></i> Monitoring Hosts
                </flux:heading>
                <flux:button variant="ghost" size="sm" wire:click="addMonitoringHost" type="button">
                    <i class="fa-solid fa-plus mr-1"></i> Host Toevoegen
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
                            placeholder="IP/Hostname"
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
                        <i class="fa-solid fa-server text-zinc-300 text-xl mb-2 block"></i>
                        Nog geen monitoring hosts toegevoegd.
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Plugins --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fa-solid fa-plug mr-2 text-indigo-500"></i> Plugins
            </flux:heading>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($availablePlugins as $plugin)
                    <label class="flex items-center gap-3 p-4 rounded-lg cursor-pointer transition border
                        {{ in_array($plugin, $selectedPlugins) ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-600 dark:bg-indigo-900/20' : 'border-zinc-200 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-750' }}">
                        <flux:checkbox wire:model="selectedPlugins" value="{{ $plugin }}" />
                        <span class="text-sm font-medium">{{ str_replace('_', ' ', ucfirst($plugin)) }}</span>
                    </label>
                @endforeach
            </div>
        </flux:card>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('bots.index') }}">
                Annuleren
            </flux:button>
            <flux:button variant="primary" type="submit">
                <i class="fa-solid fa-plus mr-1"></i> Bot Aanmaken
            </flux:button>
        </div>
    </form>
</div>
