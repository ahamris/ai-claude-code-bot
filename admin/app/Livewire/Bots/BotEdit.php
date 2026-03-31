<?php

namespace App\Livewire\Bots;

use App\Models\Bot;
use App\Services\BotConfigService;
use App\Services\BotProcessService;
use Livewire\Component;

class BotEdit extends Component
{
    public Bot $bot;
    public string $activeTab = 'algemeen';

    // Algemeen
    public string $name = '';
    public string $slug = '';
    public string $telegram_token = '';
    public string $chat_ids_input = '';
    public string $system_prompt = '';
    public bool $is_active = true;

    // AI Config
    public int $ai_max_turns = 25;
    public int $ai_timeout = 300;
    public string $ai_working_dir = '';

    // Memory
    public bool $memory_enabled = true;
    public bool $memory_auto_learn = true;
    public string $memory_data_dir = '';

    // Monitoring
    public bool $monitoring_enabled = false;
    public int $health_check_interval = 300;
    public int $security_scan_interval = 900;
    public string $formatter_mode = 'plain';
    public bool $formatter_strip_markdown = true;

    // Monitoring hosts
    public array $monitoringHosts = [];

    // SSH Hosts
    public array $sshHosts = [];

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

    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
        $this->name = $bot->name;
        $this->slug = $bot->slug;
        $this->telegram_token = '';
        $this->chat_ids_input = implode(', ', $bot->chat_ids ?? []);
        $this->system_prompt = $bot->system_prompt ?? '';
        $this->is_active = $bot->is_active;

        $this->ai_max_turns = $bot->ai_max_turns;
        $this->ai_timeout = $bot->ai_timeout;
        $this->ai_working_dir = $bot->ai_working_dir ?? '';

        $this->memory_enabled = $bot->memory_enabled;
        $this->memory_auto_learn = $bot->memory_auto_learn;
        $this->memory_data_dir = $bot->memory_data_dir ?? '';

        $this->monitoring_enabled = $bot->monitoring_enabled;
        $this->health_check_interval = $bot->health_check_interval;
        $this->security_scan_interval = $bot->security_scan_interval;
        $this->formatter_mode = $bot->formatter_mode;
        $this->formatter_strip_markdown = $bot->formatter_strip_markdown;

        // Monitoring hosts laden
        $this->monitoringHosts = $bot->monitoringHosts->map(fn ($h) => [
            'id' => $h->id,
            'group_name' => $h->group_name,
            'host_name' => $h->host_name,
            'address' => $h->address,
        ])->toArray();

        // SSH hosts laden
        $this->sshHosts = $bot->sshHosts->map(fn ($h) => [
            'id' => $h->id,
            'name' => $h->name,
            'host' => $h->host,
            'ssh_user' => $h->ssh_user,
            'jump_host' => $h->jump_host ?? '',
        ])->toArray();

        // Plugins laden
        $this->selectedPlugins = $bot->plugins->pluck('plugin_name')->toArray();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function addMonitoringHost(): void
    {
        $this->monitoringHosts[] = [
            'id' => null,
            'group_name' => '',
            'host_name' => '',
            'address' => '',
        ];
    }

    public function removeMonitoringHost(int $index): void
    {
        $host = $this->monitoringHosts[$index] ?? null;
        if ($host && !empty($host['id'])) {
            $this->bot->monitoringHosts()->where('id', $host['id'])->delete();
        }
        unset($this->monitoringHosts[$index]);
        $this->monitoringHosts = array_values($this->monitoringHosts);
    }

    public function addSshHost(): void
    {
        $this->sshHosts[] = [
            'id' => null,
            'name' => '',
            'host' => '',
            'ssh_user' => 'root',
            'jump_host' => '',
        ];
    }

    public function removeSshHost(int $index): void
    {
        $host = $this->sshHosts[$index] ?? null;
        if ($host && !empty($host['id'])) {
            $this->bot->sshHosts()->where('id', $host['id'])->delete();
        }
        unset($this->sshHosts[$index]);
        $this->sshHosts = array_values($this->sshHosts);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'system_prompt' => 'required|string',
            'ai_max_turns' => 'required|integer|min:1',
            'ai_timeout' => 'required|integer|min:30',
            'health_check_interval' => 'required|integer|min:60',
            'security_scan_interval' => 'required|integer|min:60',
        ]);

        $chatIds = array_filter(
            array_map('trim', explode(',', $this->chat_ids_input))
        );
        $chatIds = array_map('intval', $chatIds);

        $data = [
            'name' => $this->name,
            'chat_ids' => $chatIds,
            'system_prompt' => $this->system_prompt,
            'is_active' => $this->is_active,
            'ai_max_turns' => $this->ai_max_turns,
            'ai_timeout' => $this->ai_timeout,
            'ai_working_dir' => $this->ai_working_dir ?: null,
            'memory_enabled' => $this->memory_enabled,
            'memory_auto_learn' => $this->memory_auto_learn,
            'memory_data_dir' => $this->memory_data_dir ?: null,
            'monitoring_enabled' => $this->monitoring_enabled,
            'health_check_interval' => $this->health_check_interval,
            'security_scan_interval' => $this->security_scan_interval,
            'formatter_mode' => $this->formatter_mode,
            'formatter_strip_markdown' => $this->formatter_strip_markdown,
        ];

        // Token alleen updaten als het ingevuld is
        if (!empty($this->telegram_token)) {
            $data['telegram_token'] = $this->telegram_token;
        }

        $this->bot->update($data);

        // Monitoring hosts sync
        $existingIds = collect($this->monitoringHosts)->pluck('id')->filter()->toArray();
        $this->bot->monitoringHosts()->whereNotIn('id', $existingIds)->delete();

        foreach ($this->monitoringHosts as $hostData) {
            if (empty($hostData['host_name']) || empty($hostData['address'])) {
                continue;
            }

            if (!empty($hostData['id'])) {
                $this->bot->monitoringHosts()->where('id', $hostData['id'])->update([
                    'group_name' => $hostData['group_name'],
                    'host_name' => $hostData['host_name'],
                    'address' => $hostData['address'],
                ]);
            } else {
                $this->bot->monitoringHosts()->create([
                    'group_name' => $hostData['group_name'],
                    'host_name' => $hostData['host_name'],
                    'address' => $hostData['address'],
                ]);
            }
        }

        // SSH hosts sync
        $existingSshIds = collect($this->sshHosts)->pluck('id')->filter()->toArray();
        $this->bot->sshHosts()->whereNotIn('id', $existingSshIds)->delete();

        foreach ($this->sshHosts as $sshData) {
            if (empty($sshData['name']) || empty($sshData['host'])) {
                continue;
            }

            if (!empty($sshData['id'])) {
                $this->bot->sshHosts()->where('id', $sshData['id'])->update([
                    'name' => $sshData['name'],
                    'host' => $sshData['host'],
                    'ssh_user' => $sshData['ssh_user'],
                    'jump_host' => $sshData['jump_host'] ?: null,
                ]);
            } else {
                $this->bot->sshHosts()->create([
                    'name' => $sshData['name'],
                    'host' => $sshData['host'],
                    'ssh_user' => $sshData['ssh_user'],
                    'jump_host' => $sshData['jump_host'] ?: null,
                ]);
            }
        }

        // Plugins sync
        $this->bot->plugins()->delete();
        foreach ($this->selectedPlugins as $pluginName) {
            $this->bot->plugins()->create([
                'plugin_name' => $pluginName,
                'is_enabled' => true,
            ]);
        }

        // Config hergenereren en service herstarten
        app(BotConfigService::class)->syncToFilesystem($this->bot);
        app(BotProcessService::class)->restart($this->bot);

        session()->flash('message', "Bot '{$this->bot->name}' is opgeslagen en herstart.");
    }

    public function render()
    {
        return view('livewire.bots.bot-edit')
            ->layout('layouts.app');
    }
}
