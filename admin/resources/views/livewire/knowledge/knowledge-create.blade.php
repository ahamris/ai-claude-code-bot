<div>
    <div class="mb-6">
        <a href="{{ route('bots.knowledge', $bot) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Terug naar knowledge</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nieuw Knowledge Item - {{ $bot->name }}</h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Handmatig Invoeren</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                    <input type="text" wire:model="topic"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                           placeholder="Bijv. Server Configuratie">
                    @error('topic') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    <textarea wire:model="content" rows="10"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                              placeholder="Kennis content in Markdown..."></textarea>
                    @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Of: Bestand Uploaden</h2>

            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                <input type="file" wire:model="file" class="hidden" id="file-upload">
                <label for="file-upload" class="cursor-pointer">
                    <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p class="mt-2 text-sm text-gray-600">
                        <span class="font-medium text-indigo-600 hover:text-indigo-500">Klik om een bestand te selecteren</span>
                        of sleep het hierheen
                    </p>
                    <p class="mt-1 text-xs text-gray-500">MD, TXT, PDF, DOC, DOCX tot 10MB</p>
                </label>

                @if($file)
                    <div class="mt-4 p-3 bg-indigo-50 rounded-lg inline-flex items-center gap-2 text-sm text-indigo-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ $file->getClientOriginalName() }}
                    </div>
                @endif

                @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('bots.knowledge', $bot) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuleren
            </a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                Opslaan
            </button>
        </div>
    </form>
</div>
