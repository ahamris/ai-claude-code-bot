<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\File;

class BotConfigService
{
    public function generateYaml(Bot $bot): string
    {
        $bot->load([
            'monitoringHosts',
            'monitoringWebServices',
            'sshHosts',
            'plugins',
        ]);

        return $bot->generateYamlConfig();
    }

    public function writeConfig(Bot $bot): void
    {
        $configsPath = config('aibot.configs_path');
        File::ensureDirectoryExists($configsPath);

        $yaml = $this->generateYaml($bot);
        $filePath = $configsPath . '/' . $bot->slug . '.yaml';

        File::put($filePath, $yaml);
    }

    public function syncToFilesystem(Bot $bot): void
    {
        // Schrijf de YAML config
        $this->writeConfig($bot);

        // Sync knowledge bestanden
        app(KnowledgeSyncService::class)->syncToFilesystem($bot);
    }
}
