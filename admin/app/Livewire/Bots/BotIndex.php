<?php

namespace App\Livewire\Bots;

use App\Models\Bot;
use App\Services\BotProcessService;
use Livewire\Component;

class BotIndex extends Component
{
    public function startBot(int $botId): void
    {
        $bot = Bot::findOrFail($botId);
        app(BotProcessService::class)->start($bot);
        session()->flash('message', "Bot '{$bot->name}' wordt gestart.");
    }

    public function stopBot(int $botId): void
    {
        $bot = Bot::findOrFail($botId);
        app(BotProcessService::class)->stop($bot);
        session()->flash('message', "Bot '{$bot->name}' wordt gestopt.");
    }

    public function restartBot(int $botId): void
    {
        $bot = Bot::findOrFail($botId);
        app(BotProcessService::class)->restart($bot);
        session()->flash('message', "Bot '{$bot->name}' wordt herstart.");
    }

    public function render()
    {
        $bots = Bot::withCount(['knowledgeItems', 'conversations'])->latest()->get();

        $processService = app(BotProcessService::class);
        $bots->each(function ($bot) use ($processService) {
            $bot->process_status = $processService->status($bot);
        });

        return view('livewire.bots.bot-index', [
            'bots' => $bots,
        ])->layout('layouts.app');
    }
}
