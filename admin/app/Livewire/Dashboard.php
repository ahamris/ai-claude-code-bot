<?php

namespace App\Livewire;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\KnowledgeItem;
use App\Services\BotProcessService;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $totalBots = Bot::count();
        $activeBots = Bot::active()->count();
        $knowledgeCount = KnowledgeItem::count();
        $conversationsToday = Conversation::whereDate('created_at', today())->count();
        $bots = Bot::withCount(['knowledgeItems', 'conversations'])->latest()->get();

        // Voeg status toe aan elke bot
        $processService = app(BotProcessService::class);
        $bots->each(function ($bot) use ($processService) {
            $bot->process_status = $processService->status($bot);
        });

        return view('livewire.dashboard', [
            'totalBots' => $totalBots,
            'activeBots' => $activeBots,
            'knowledgeCount' => $knowledgeCount,
            'conversationsToday' => $conversationsToday,
            'bots' => $bots,
        ])->layout('layouts.app');
    }
}
