<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Process;

class BotProcessService
{
    protected function unitName(Bot $bot): string
    {
        return config('aibot.systemd_unit_prefix') . $bot->slug;
    }

    public function start(Bot $bot): bool
    {
        $unit = $this->unitName($bot);
        $result = Process::run("sudo systemctl start {$unit}");

        return $result->successful();
    }

    public function stop(Bot $bot): bool
    {
        $unit = $this->unitName($bot);
        $result = Process::run("sudo systemctl stop {$unit}");

        return $result->successful();
    }

    public function restart(Bot $bot): bool
    {
        $unit = $this->unitName($bot);
        $result = Process::run("sudo systemctl restart {$unit}");

        return $result->successful();
    }

    public function status(Bot $bot): string
    {
        $unit = $this->unitName($bot);

        try {
            $result = Process::run("systemctl is-active {$unit}");
            return trim($result->output());
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    public function logs(Bot $bot, int $lines = 50): string
    {
        $unit = $this->unitName($bot);

        try {
            $result = Process::run("journalctl -u {$unit} -n {$lines} --no-pager");
            return $result->output();
        } catch (\Throwable) {
            return 'Logs niet beschikbaar.';
        }
    }
}
