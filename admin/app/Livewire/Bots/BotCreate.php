<?php

namespace App\Livewire\Bots;

use App\Models\Bot;
use App\Services\BotConfigService;
use App\Services\BotProcessService;
use Illuminate\Support\Str;
use Livewire\Component;

class BotCreate extends Component
{
    public string $name = '';
    public string $slug = '';
    public string $telegram_token = '';
    public string $chat_ids_input = '';
    public string $system_prompt = '';

    // Monitoring hosts (dynamic)
    public array $monitoringHosts = [];

    // Plugins
    public array $availablePlugins = [
        'monitoring',
        'knowledge',
        'ssh_executor',
        'security_scanner',
        'backup_manager',
        'log_analyzer',
    ];
    public array $selectedPlugins = [];

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function addMonitoringHost(): void
    {
        $this->monitoringHosts[] = [
            'group_name' => '',
            'host_name' => '',
            'address' => '',
        ];
    }

    public function removeMonitoringHost(int $index): void
    {
        unset($this->monitoringHosts[$index]);
        $this->monitoringHosts = array_values($this->monitoringHosts);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:bots,slug',
            'telegram_token' => 'required|string',
            'system_prompt' => 'required|string',
            'monitoringHosts.*.group_name' => 'required_with:monitoringHosts.*.host_name|string|max:255',
            'monitoringHosts.*.host_name' => 'required_with:monitoringHosts.*.address|string|max:255',
            'monitoringHosts.*.address' => 'required_with:monitoringHosts.*.host_name|string|max:255',
        ]);

        // Parse chat_ids
        $chatIds = array_filter(
            array_map('trim', explode(',', $this->chat_ids_input))
        );
        $chatIds = array_map('intval', $chatIds);

        $bot = Bot::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'telegram_token' => $this->telegram_token,
            'chat_ids' => $chatIds,
            'system_prompt' => $this->system_prompt,
            'monitoring_enabled' => !empty($this->monitoringHosts),
        ]);

        // Monitoring hosts aanmaken
        foreach ($this->monitoringHosts as $host) {
            if (!empty($host['host_name']) && !empty($host['address'])) {
                $bot->monitoringHosts()->create($host);
            }
        }

        // Plugins aanmaken
        foreach ($this->selectedPlugins as $pluginName) {
            $bot->plugins()->create([
                'plugin_name' => $pluginName,
                'is_enabled' => true,
            ]);
        }

        // Config schrijven en service starten
        app(BotConfigService::class)->syncToFilesystem($bot);
        app(BotProcessService::class)->start($bot);

        session()->flash('message', "Bot '{$bot->name}' is aangemaakt en gestart.");
        $this->redirect(route('bots.edit', $bot), navigate: true);
    }

    public function render()
    {
        return view('livewire.bots.bot-create')
            ->layout('layouts.app');
    }
}
