<div>
    <div class="mb-6">
        <flux:link href="{{ route('bots.knowledge', $bot) }}" class="text-sm text-zinc-500">
            <i class="fa-solid fa-arrow-left mr-1"></i> Terug naar knowledge
        </flux:link>
        <flux:heading size="xl" class="mt-2">
            <i class="fa-solid fa-brain mr-2 text-indigo-500"></i> Nieuw Knowledge Item - {{ $bot->name }}
        </flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Handmatig invoeren --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fa-solid fa-pen-to-square mr-2 text-indigo-500"></i> Handmatig Invoeren
            </flux:heading>

            <div class="space-y-4">
                <flux:input
                    wire:model="topic"
                    label="Topic"
                    placeholder="Bijv. Server Configuratie"
                />

                <flux:textarea
                    wire:model="content"
                    label="Content"
                    rows="10"
                    placeholder="Kennis content in Markdown..."
                    class="font-mono"
                />
            </div>
        </flux:card>

        {{-- Bestand uploaden --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fa-solid fa-file-arrow-up mr-2 text-indigo-500"></i> Of: Bestand Uploaden
            </flux:heading>

            <flux:file-upload wire:model="file" label="Bestand" description="MD, TXT, PDF, DOC, DOCX tot 10MB" />

            <div class="mt-2 p-6 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg text-center">
                <i class="fa-solid fa-cloud-arrow-up text-3xl text-zinc-400 mb-3 block"></i>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Sleep bestanden hierheen of gebruik de upload knop hierboven
                </p>
            </div>

            @if($file)
                <div class="mt-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg inline-flex items-center gap-2 text-sm text-indigo-700 dark:text-indigo-300">
                    <i class="fa-solid fa-file"></i>
                    {{ $file->getClientOriginalName() }}
                </div>
            @endif

            @error('file')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </flux:card>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('bots.knowledge', $bot) }}">
                Annuleren
            </flux:button>
            <flux:button variant="primary" type="submit">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Opslaan
            </flux:button>
        </div>
    </form>
</div>
