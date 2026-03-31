<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\KnowledgeItem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class KnowledgeSyncService
{
    protected function knowledgePath(Bot $bot): string
    {
        $dataDir = $bot->memory_data_dir
            ?: config('aibot.data_base_path') . '/' . $bot->slug;

        return $dataDir . '/memory/knowledge';
    }

    public function syncToFilesystem(Bot $bot): void
    {
        $path = $this->knowledgePath($bot);
        File::ensureDirectoryExists($path);

        // Verwijder bestaande bestanden die via admin zijn aangemaakt
        $existingFiles = File::glob($path . '/*.md');
        foreach ($existingFiles as $file) {
            File::delete($file);
        }

        // Schrijf alle knowledge items als .md bestanden
        foreach ($bot->knowledgeItems as $item) {
            $filename = Str::slug($item->topic) . '.md';
            $content = "# {$item->topic}\n\n{$item->content}\n";
            File::put($path . '/' . $filename, $content);
        }
    }

    public function importFromFilesystem(Bot $bot): int
    {
        $path = $this->knowledgePath($bot);

        if (!File::isDirectory($path)) {
            return 0;
        }

        $files = File::glob($path . '/*.md');
        $imported = 0;

        foreach ($files as $file) {
            $content = File::get($file);
            $filename = pathinfo($file, PATHINFO_FILENAME);

            // Probeer de titel uit de eerste # heading te halen
            $topic = $filename;
            if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
                $topic = trim($matches[1]);
                // Verwijder de heading uit de content
                $content = trim(preg_replace('/^#\s+.+\n*/m', '', $content, 1));
            }

            // Check of het item al bestaat (op basis van topic)
            $existing = $bot->knowledgeItems()->where('topic', $topic)->first();

            if (!$existing) {
                $bot->knowledgeItems()->create([
                    'topic' => $topic,
                    'content' => $content,
                    'source' => 'filesystem',
                ]);
                $imported++;
            }
        }

        return $imported;
    }
}
