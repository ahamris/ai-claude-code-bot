<?php

namespace App\Livewire\Monitoring;

use App\Models\Bot;
use App\Models\HealthCheck;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MonitoringDashboard extends Component
{
    public Bot $bot;

    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
    }

    #[Computed]
    public function hostStatuses(): array
    {
        $hosts = $this->bot->monitoringHosts;
        $statuses = [];

        foreach ($hosts as $host) {
            $latestCheck = HealthCheck::where('bot_id', $this->bot->id)
                ->where('host_name', $host->host_name)
                ->latest('checked_at')
                ->first();

            // Uptime percentage over de laatste 24 uur
            $totalChecks = HealthCheck::where('bot_id', $this->bot->id)
                ->where('host_name', $host->host_name)
                ->where('checked_at', '>=', now()->subDay())
                ->count();

            $successfulChecks = HealthCheck::where('bot_id', $this->bot->id)
                ->where('host_name', $host->host_name)
                ->where('checked_at', '>=', now()->subDay())
                ->where('reachable', true)
                ->count();

            $uptimePercentage = $totalChecks > 0
                ? round(($successfulChecks / $totalChecks) * 100, 1)
                : 0;

            $statuses[] = [
                'host' => $host,
                'latest_check' => $latestCheck,
                'reachable' => $latestCheck?->reachable ?? false,
                'latency' => $latestCheck?->latency_ms ?? 0,
                'last_checked' => $latestCheck?->checked_at,
                'uptime_24h' => $uptimePercentage,
            ];
        }

        return $statuses;
    }

    public function render()
    {
        return view('livewire.monitoring.monitoring-dashboard')
            ->layout('layouts.app');
    }
}
