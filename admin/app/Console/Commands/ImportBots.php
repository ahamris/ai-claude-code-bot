<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Conversation;
use App\Services\KnowledgeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class ImportBots extends Command
{
    protected $signature = 'bots:import';

    protected $description = 'Importeer bots vanuit YAML configuratiebestanden';

    public function handle(): int
    {
        $configsPath = config('aibot.configs_path');
        $dataBasePath = config('aibot.data_base_path');

        if (!File::isDirectory($configsPath)) {
            $this->error("Configs directory niet gevonden: {$configsPath}");
            return self::FAILURE;
        }

        $yamlFiles = File::glob($configsPath . '/*.yaml');

        if (empty($yamlFiles)) {
            $this->warn('Geen YAML bestanden gevonden.');
            return self::SUCCESS;
        }

        $this->info("Gevonden: " . count($yamlFiles) . " configuratiebestand(en).");

        foreach ($yamlFiles as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $this->info("Importeer: {$slug}");

            try {
                $config = Yaml::parseFile($file);
            } catch (\Throwable $e) {
                $this->error("  Fout bij parsen van {$file}: {$e->getMessage()}");
                continue;
            }

            $botConfig = $config['bot'] ?? [];
            $aiConfig = $config['ai'] ?? [];
            $memoryConfig = $config['memory'] ?? [];
            $monitoringConfig = $config['monitoring'] ?? [];
            $formatterConfig = $config['formatter'] ?? [];

            // Bot aanmaken of bijwerken
            $bot = Bot::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $botConfig['name'] ?? Str::title(str_replace('-', ' ', $slug)),
                    'telegram_token' => $botConfig['telegram_token'] ?? '',
                    'chat_ids' => $botConfig['allowed_chat_ids'] ?? [],
                    'system_prompt' => $config['system_prompt'] ?? '',
                    'ai_max_turns' => $aiConfig['max_turns'] ?? 25,
                    'ai_timeout' => $aiConfig['timeout'] ?? 300,
                    'ai_working_dir' => $aiConfig['working_dir'] ?? null,
                    'memory_enabled' => $memoryConfig['enabled'] ?? true,
                    'memory_auto_learn' => $memoryConfig['auto_learn'] ?? true,
                    'memory_data_dir' => $memoryConfig['data_dir'] ?? null,
                    'monitoring_enabled' => $monitoringConfig['enabled'] ?? false,
                    'health_check_interval' => $monitoringConfig['health_check_interval'] ?? 300,
                    'security_scan_interval' => $monitoringConfig['security_scan_interval'] ?? 900,
                    'formatter_mode' => $formatterConfig['mode'] ?? 'plain',
                    'formatter_strip_markdown' => $formatterConfig['strip_markdown'] ?? true,
                ]
            );

            // Monitoring hosts importeren
            if (!empty($monitoringConfig['hosts'])) {
                $bot->monitoringHosts()->delete();
                foreach ($monitoringConfig['hosts'] as $host) {
                    $bot->monitoringHosts()->create([
                        'group_name' => $host['group'] ?? 'default',
                        'host_name' => $host['name'] ?? '',
                        'address' => $host['address'] ?? '',
                    ]);
                }
                $this->line("  - " . count($monitoringConfig['hosts']) . " monitoring host(s) geimporteerd");
            }

            // Web services importeren
            if (!empty($monitoringConfig['web_services'])) {
                $bot->monitoringWebServices()->delete();
                foreach ($monitoringConfig['web_services'] as $service) {
                    $bot->monitoringWebServices()->create([
                        'name' => $service['name'] ?? '',
                        'host' => $service['host'] ?? '',
                        'port' => $service['port'] ?? 443,
                    ]);
                }
                $this->line("  - " . count($monitoringConfig['web_services']) . " web service(s) geimporteerd");
            }

            // SSH hosts importeren
            if (!empty($config['ssh_hosts'])) {
                $bot->sshHosts()->delete();
                foreach ($config['ssh_hosts'] as $sshHost) {
                    $bot->sshHosts()->create([
                        'name' => $sshHost['name'] ?? '',
                        'host' => $sshHost['host'] ?? '',
                        'ssh_user' => $sshHost['user'] ?? 'root',
                        'jump_host' => $sshHost['jump_host'] ?? null,
                    ]);
                }
                $this->line("  - " . count($config['ssh_hosts']) . " SSH host(s) geimporteerd");
            }

            // Plugins importeren
            if (!empty($config['plugins'])) {
                $bot->plugins()->delete();
                foreach ($config['plugins'] as $pluginName => $pluginConfig) {
                    $bot->plugins()->create([
                        'plugin_name' => $pluginName,
                        'is_enabled' => $pluginConfig['enabled'] ?? true,
                        'config' => $pluginConfig['config'] ?? [],
                    ]);
                }
                $this->line("  - " . count($config['plugins']) . " plugin(s) geimporteerd");
            }

            // Knowledge items importeren vanuit filesystem
            $knowledgeService = app(KnowledgeSyncService::class);
            $imported = $knowledgeService->importFromFilesystem($bot);
            if ($imported > 0) {
                $this->line("  - {$imported} knowledge item(s) geimporteerd");
            }

            // Conversations importeren vanuit JSON bestanden
            $dataDir = $bot->memory_data_dir ?: ($dataBasePath . '/' . $slug);
            $conversationsDir = $dataDir . '/conversations';
            if (File::isDirectory($conversationsDir)) {
                $jsonFiles = File::glob($conversationsDir . '/*.json');
                $conversationCount = 0;

                foreach ($jsonFiles as $jsonFile) {
                    try {
                        $data = json_decode(File::get($jsonFile), true);
                        if (!is_array($data)) {
                            continue;
                        }

                        // Ondersteun zowel enkel gesprek als array van gesprekken
                        $entries = isset($data['user_message']) ? [$data] : $data;

                        foreach ($entries as $entry) {
                            if (empty($entry['user_message']) || empty($entry['assistant_message'])) {
                                continue;
                            }

                            Conversation::firstOrCreate(
                                [
                                    'bot_id' => $bot->id,
                                    'user_message' => $entry['user_message'],
                                    'created_at' => $entry['created_at'] ?? now(),
                                ],
                                [
                                    'telegram_user_id' => $entry['telegram_user_id'] ?? null,
                                    'assistant_message' => $entry['assistant_message'],
                                ]
                            );
                            $conversationCount++;
                        }
                    } catch (\Throwable $e) {
                        $this->warn("  Fout bij importeren van {$jsonFile}: {$e->getMessage()}");
                    }
                }

                if ($conversationCount > 0) {
                    $this->line("  - {$conversationCount} gesprek(ken) geimporteerd");
                }
            }

            $this->info("  Bot '{$bot->name}' succesvol geimporteerd.");
        }

        $this->newLine();
        $this->info('Import voltooid.');

        return self::SUCCESS;
    }
}
