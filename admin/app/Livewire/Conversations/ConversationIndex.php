<?php

namespace App\Livewire\Conversations;

use App\Models\Bot;
use App\Models\Conversation;
use Livewire\Component;

class ConversationIndex extends Component
{
    public Bot $bot;
    public ?int $selectedId = null;
    public string $dateFilter = '';

    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
    }

    public function selectConversation(?int $id): void
    {
        $this->selectedId = $this->selectedId === $id ? null : $id;
    }

    public function render()
    {
        $query = $this->bot->conversations()->latest('created_at');

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        $conversations = $query->paginate(25);

        $selected = $this->selectedId
            ? Conversation::find($this->selectedId)
            : null;

        return view('livewire.conversations.conversation-index', [
            'conversations' => $conversations,
            'selected' => $selected,
        ])->layout('layouts.app');
    }
}
