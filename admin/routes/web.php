<?php

use App\Livewire\Bots\BotCreate;
use App\Livewire\Bots\BotEdit;
use App\Livewire\Bots\BotIndex;
use App\Livewire\Conversations\ConversationIndex;
use App\Livewire\Dashboard;
use App\Livewire\Knowledge\KnowledgeCreate;
use App\Livewire\Knowledge\KnowledgeIndex;
use App\Livewire\Logs\LogViewer;
use App\Livewire\Monitoring\MonitoringDashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/bots', BotIndex::class)->name('bots.index');
    Route::get('/bots/create', BotCreate::class)->name('bots.create');
    Route::get('/bots/{bot:slug}', BotEdit::class)->name('bots.edit');
    Route::get('/bots/{bot:slug}/knowledge', KnowledgeIndex::class)->name('bots.knowledge');
    Route::get('/bots/{bot:slug}/knowledge/create', KnowledgeCreate::class)->name('bots.knowledge.create');
    Route::get('/bots/{bot:slug}/conversations', ConversationIndex::class)->name('bots.conversations');
    Route::get('/bots/{bot:slug}/monitoring', MonitoringDashboard::class)->name('bots.monitoring');
    Route::get('/bots/{bot:slug}/logs', LogViewer::class)->name('bots.logs');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
