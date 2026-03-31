<?php

namespace App\Livewire\Knowledge;

use App\Models\Bot;
use App\Services\KnowledgeSyncService;
use Livewire\Component;
use Livewire\WithFileUploads;

class KnowledgeCreate extends Component
{
    use WithFileUploads;

    public Bot $bot;
    public string $topic = '';
    public string $content = '';
    public $file = null;

    public function mount(Bot $bot): void
    {
        $this->bot = $bot;
    }

    public function save(): void
    {
        if ($this->file) {
            $this->validate([
                'file' => 'file|max:10240|mimes:md,txt,pdf,doc,docx',
            ]);

            $originalName = $this->file->getClientOriginalName();
            $topic = pathinfo($originalName, PATHINFO_FILENAME);
            $content = file_get_contents($this->file->getRealPath());

            $this->bot->knowledgeItems()->create([
                'topic' => $topic,
                'content' => $content,
                'source' => 'upload',
            ]);

            // Sla het ook op als document
            $path = $this->file->store('documents/' . $this->bot->slug, 'local');
            $this->bot->documents()->create([
                'original_filename' => $originalName,
                'stored_path' => $path,
                'mime_type' => $this->file->getMimeType(),
                'size_bytes' => $this->file->getSize(),
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } else {
            $this->validate([
                'topic' => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $this->bot->knowledgeItems()->create([
                'topic' => $this->topic,
                'content' => $this->content,
                'source' => 'manual',
            ]);
        }

        app(KnowledgeSyncService::class)->syncToFilesystem($this->bot);

        session()->flash('message', 'Knowledge item aangemaakt.');
        $this->redirect(route('bots.knowledge', $this->bot), navigate: true);
    }

    public function render()
    {
        return view('livewire.knowledge.knowledge-create')
            ->layout('layouts.app');
    }
}
