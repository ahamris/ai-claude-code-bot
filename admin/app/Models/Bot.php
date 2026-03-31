<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

class Bot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'chat_ids' => 'array',
            'telegram_token' => 'encrypted',
            'is_active' => 'boolean',
            'memory_enabled' => 'boolean',
            'memory_auto_learn' => 'boolean',
            'monitoring_enabled' => 'boolean',
            'formatter_strip_markdown' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    // Relations

    public function monitoringHosts(): HasMany
    {
        return $this->hasMany(MonitoringHost::class);
    }

    public function monitoringWebServices(): HasMany
    {
        return $this->hasMany(MonitoringWebService::class);
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(HealthCheck::class);
    }

    public function plugins(): HasMany
    {
        return $this->hasMany(BotPlugin::class);
    }

    public function knowledgeItems(): HasMany
    {
        return $this->hasMany(KnowledgeItem::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function sshHosts(): HasMany
    {
        return $this->hasMany(SshHost::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Attributes

    public function getStatusAttribute(): string
    {
        $unit = config('aibot.systemd_unit_prefix') . $this->slug;

        try {
            $result = Process::run("systemctl is-active {$unit}");
            return trim($result->output());
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    // Methods

    public function generateYamlConfig(): string
    {
        $config = [
            'bot' => [
                'name' => $this->name,
                'telegram_token' => $this->getAttributes()['telegram_token'] ?? '',
                'allowed_chat_ids' => $this->chat_ids ?? [],
            ],
            'ai' => [
                'max_turns' => $this->ai_max_turns,
                'timeout' => $this->ai_timeout,
            ],
            'memory' => [
                'enabled' => $this->memory_enabled,
                'auto_learn' => $this->memory_auto_learn,
            ],
            'monitoring' => [
                'enabled' => $this->monitoring_enabled,
                'health_check_interval' => $this->health_check_interval,
                'security_scan_interval' => $this->security_scan_interval,
            ],
            'formatter' => [
                'mode' => $this->formatter_mode,
                'strip_markdown' => $this->formatter_strip_markdown,
            ],
            'system_prompt' => $this->system_prompt,
        ];

        if ($this->ai_working_dir) {
            $config['ai']['working_dir'] = $this->ai_working_dir;
        }

        if ($this->memory_data_dir) {
            $config['memory']['data_dir'] = $this->memory_data_dir;
        }

        // Monitoring hosts
        if ($this->monitoringHosts->isNotEmpty()) {
            $hosts = [];
            foreach ($this->monitoringHosts as $host) {
                $hosts[] = [
                    'group' => $host->group_name,
                    'name' => $host->host_name,
                    'address' => $host->address,
                ];
            }
            $config['monitoring']['hosts'] = $hosts;
        }

        // Web services
        if ($this->monitoringWebServices->isNotEmpty()) {
            $services = [];
            foreach ($this->monitoringWebServices as $service) {
                $services[] = [
                    'name' => $service->name,
                    'host' => $service->host,
                    'port' => $service->port,
                ];
            }
            $config['monitoring']['web_services'] = $services;
        }

        // SSH hosts
        if ($this->sshHosts->isNotEmpty()) {
            $sshHosts = [];
            foreach ($this->sshHosts as $sshHost) {
                $entry = [
                    'name' => $sshHost->name,
                    'host' => $sshHost->host,
                    'user' => $sshHost->ssh_user,
                ];
                if ($sshHost->jump_host) {
                    $entry['jump_host'] = $sshHost->jump_host;
                }
                $sshHosts[] = $entry;
            }
            $config['ssh_hosts'] = $sshHosts;
        }

        // Plugins
        if ($this->plugins->isNotEmpty()) {
            $plugins = [];
            foreach ($this->plugins as $plugin) {
                $plugins[$plugin->plugin_name] = [
                    'enabled' => $plugin->is_enabled,
                    'config' => $plugin->config ?? [],
                ];
            }
            $config['plugins'] = $plugins;
        }

        return Yaml::dump($config, 6, 2);
    }
}
