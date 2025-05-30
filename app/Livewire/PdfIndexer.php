<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\ProcessPdfKeywordExtraction;
use App\Services\PdfKeywordIndexerService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

final class PdfIndexer extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:pdf|max:10240')]
    public $pdfFile;

    public string $systemPrompt = 'Du bist Informatikexperte und Keyword-Extractor. Wenn dir ein Text gegeben wird, extrahiere bitte alle relevanten Informatikbegriffe, bringe sie in ihre Singularform und gebe sie als Liste zurück. Fokussiere dich auf technische Begriffe, Konzepte, Algorithmen, Programmiersprachen, Frameworks, Tools und andere wichtige Informatikthemen.';

    public array $extractedKeywords = [];

    public bool $isProcessing = false;

    public int $currentPage = 0;

    public int $totalPages = 0;

    public string $errorMessage = '';

    public string $successMessage = '';

    public string $sessionId = '';

    public function mount(): void
    {
        $this->sessionId = Str::uuid()->toString();
    }

    public function updatedPdfFile(): void
    {
        $this->reset([
            'errorMessage',
            'successMessage',
            'extractedKeywords',
            'currentPage',
            'totalPages',
        ]);
        $this->validate();
    }

    public function extractKeywords(): void
    {
        $this->validate();

        $this->reset([
            'errorMessage',
            'successMessage',
            'extractedKeywords',
            'currentPage',
            'totalPages',
        ]);

        $this->isProcessing = true;

        // Store file temporarily
        $tempPath = $this->pdfFile->store('temp-pdfs');
        $fullPath = Storage::path($tempPath);

        // Dispatch job
        ProcessPdfKeywordExtraction::dispatch(
            $fullPath,
            $this->sessionId,
            $this->systemPrompt
        );

        $this->successMessage = 'PDF queued for processing. Please wait...';
    }

    #[On('echo:pdf-indexing.{sessionId},progress')]
    public function onProgress(array $data): void
    {
        $this->currentPage = $data['current_page'];
        $this->totalPages = $data['total_pages'];
        $this->successMessage = "Processing page {$this->currentPage} of {$this->totalPages}...";
    }

    #[On('echo:pdf-indexing.{sessionId},completed')]
    public function onCompleted(array $data): void
    {
        $this->extractedKeywords = $data['keywords'];
        $this->isProcessing = false;
        $this->successMessage = "PDF successfully processed! Extracted {$data['keyword_count']} unique keywords.";
    }

    #[On('echo:pdf-indexing.{sessionId},failed')]
    public function onFailed(array $data): void
    {
        $this->isProcessing = false;
        $this->errorMessage = "Error processing PDF: {$data['error_message']}";
        $this->successMessage = '';
    }

    public function downloadIndex(): mixed
    {
        if (empty($this->extractedKeywords)) {
            return null;
        }

        $indexerService = app(PdfKeywordIndexerService::class);
        $content = $indexerService->generateIndexFile($this->extractedKeywords);

        return response()->streamDownload(
            fn () => print ($content),
            'pdf-index.txt',
            ['Content-Type' => 'text/plain']
        );
    }

    public function render()
    {
        return view('livewire.pdf-indexer');
    }

    public function getListeners(): array
    {
        return [
            "echo:pdf-indexing.{$this->sessionId},progress" => 'onProgress',
            "echo:pdf-indexing.{$this->sessionId},completed" => 'onCompleted',
            "echo:pdf-indexing.{$this->sessionId},failed" => 'onFailed',
        ];
    }
}
