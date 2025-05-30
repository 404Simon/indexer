<div class="max-w-4xl mx-auto p-6 space-y-6">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">PDF Keyword Indexer</h1>
        <p class="text-gray-600 dark:text-gray-400">Upload a PDF and extract relevant keywords with AI-powered analysis
        </p>
    </div>

    {{-- File Upload Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Upload PDF Document</h2>

        <div class="space-y-4">
            @if ($pdfFile)
                {{-- File Preview --}}
                <div class="p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-3" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ $pdfFile->getClientOriginalName() }}</p>
                            <p class="text-xs text-red-600 dark:text-red-400">
                                {{ number_format($pdfFile->getSize() / 1024, 1) }} KB</p>
                        </div>
                    </div>
                </div>

                {{-- Change File Button --}}
                <div class="flex justify-center">
                    <label for="pdf-upload"
                        class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Change File
                    </label>
                </div>
            @else
                {{-- Drag & Drop Area --}}
                <label for="pdf-upload" class="block cursor-pointer">
                    <div x-data="{ dragOver: false }" @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))"
                        :class="dragOver ? 'border-blue-400 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20' :
                            'border-gray-300 dark:border-gray-600'"
                        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors duration-200 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Drop your PDF here or click to browse
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Maximum file size: 10MB
                        </p>
                    </div>
                </label>
            @endif

            {{-- Hidden File Input --}}
            <input id="pdf-upload" type="file" x-ref="fileInput" wire:model="pdfFile" accept=".pdf" class="sr-only">

            @error('pdfFile')
                <div class="p-3 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-red-800 dark:text-red-200 font-medium text-sm">{{ $message }}</p>
                    </div>
                </div>
            @enderror
        </div>
    </div>

    {{-- System Prompt Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">AI System Prompt</h2>

        <textarea wire:model="systemPrompt" rows="4" placeholder="Enter the system prompt for the AI..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"></textarea>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Customize this prompt to guide the AI in extracting keywords relevant to your document's topic.
        </p>
    </div>

    {{-- Action Button --}}
    <div class="flex justify-center">
        <button wire:click="extractKeywords" @disabled($pdfFile === null || $isProcessing)
            class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
            @if ($isProcessing)
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Processing...
            @else
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Extract Keywords
            @endif
        </button>
    </div>

    {{-- Progress Section --}}
    @if ($isProcessing && $totalPages > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Processing Progress
            </h2>

            <div class="space-y-3">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Page {{ $currentPage }} of {{ $totalPages }}</span>
                    <span>{{ $totalPages > 0 ? round(($currentPage / $totalPages) * 100) : 0 }}%</span>
                </div>

                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300"
                        style="width: {{ $totalPages > 0 ? ($currentPage / $totalPages) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Messages --}}
    @if ($errorMessage)
        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-red-800 dark:text-red-200 font-medium">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    @if ($successMessage)
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-green-800 dark:text-green-200 font-medium">{{ $successMessage }}</p>
            </div>
        </div>
    @endif

    {{-- Results Section --}}
    @if (!empty($extractedKeywords))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Extracted Keywords Index</h2>
                <button wire:click="downloadIndex"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download
                </button>
            </div>

            <div
                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700">
                <div class="space-y-2 font-mono text-sm">
                    @foreach ($extractedKeywords as $keyword => $pages)
                        <div
                            class="flex justify-between items-center py-1 hover:bg-gray-100 dark:hover:bg-gray-800 px-2 rounded">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $keyword }}</span>
                            <span
                                class="text-gray-600 dark:text-gray-400 text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded border border-gray-200 dark:border-gray-600">[{{ implode(', ', $pages) }}]</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                Found {{ count($extractedKeywords) }} unique keywords. Numbers in brackets indicate page numbers where
                the keyword appears.
            </p>
        </div>
    @endif
</div>
