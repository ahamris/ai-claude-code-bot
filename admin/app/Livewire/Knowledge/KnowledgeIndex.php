<?php

namespace App\Livewire\Knowledge;

use App\Models\Bot;
use App\Models\KnowledgeItem;
use App\Services\KnowledgeSyncService;
use Livewire\Component;

class KnowledgeIndex extends Component
{
    public Bot $bot;
    public ?int $editingId = null;
    public string $editContent = '';
    public string $editTopic = '';

    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
    }

    public function startEdit(int $itemId): void
    {
        $item = KnowledgeItem::findOrFail($itemId);
        $this->editingId = $item->id;
        $this->editTopic = $item->topic;
        $this->editContent = $item->content;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editTopic = '';
        $this->editContent = '';
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editTopic' => 'required|string|max:255',
            'editContent' => 'required|string',
        ]);

        $item = KnowledgeItem::findOrFail($this->editingId);
        $item->update([
            'topic' => $this->editTopic,
            'content' => $this->editContent,
        ]);

        app(KnowledgeSyncService::class)->syncToFilesystem($this->bot);

        $this->cancelEdit();
        session()->flash('message', 'Knowledge item bijgewerkt.');
    }

    public function delete(int $itemId): void
    {
        $item = KnowledgeItem::findOrFail($itemId);
        $item->delete();

        app(KnowledgeSyncService::class)->syncToFilesystem($this->bot);

        session()->flash('message', 'Knowledge item verwijderd.');
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-index', [
            'items' => $this->bot->knowledgeItems()->latest()->get(),
        ])->layout('layouts.app');
    }
}
