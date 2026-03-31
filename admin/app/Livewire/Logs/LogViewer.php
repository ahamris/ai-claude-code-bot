<?php

namespace App\Livewire\Logs;

use App\Models\Bot;
use App\Services\BotProcessService;
use Livewire\Attributes\Polling;
use Livewire\Component;

class LogViewer extends Component
{
    public Bot $bot;
    public int $lines = 100;
    public string $severityFilter = '';

    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
    }

    #[Polling('5s')]
    public function render()
    {
        $logs = app(BotProcessService::class)->logs($this->bot, $this->lines);

        // Filter op severity als ingesteld
        if ($this->severityFilter) {
            $filteredLines = array_filter(
                explode("\n", $logs),
                fn ($line) => stripos($line, $this->severityFilter) !== false
            );
            $logs = implode("\n", $filteredLines);
        }

        return view('livewire.logs.log-viewer', [
            'logs' => $logs,
        ])->layout('layouts.app');
    }
}
