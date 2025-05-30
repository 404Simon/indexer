<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\PdfIndexingFailed;
use App\Services\PdfKeywordIndexerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ProcessPdfKeywordExtraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $filePath,
        public readonly string $sessionId,
        public readonly ?string $systemPrompt = null,
    ) {}

    public function handle(PdfKeywordIndexerService $indexerService): void
    {
        try {
            Log::info('Starting PDF keyword extraction', [
                'file_path' => $this->filePath,
                'session_id' => $this->sessionId,
            ]);

            $keywords = $indexerService->processDocument(
                $this->filePath,
                $this->systemPrompt,
                $this->sessionId
            );

            Log::info('PDF keyword extraction completed', [
                'session_id' => $this->sessionId,
                'keyword_count' => count($keywords),
            ]);

        } catch (Exception $e) {
            Log::error('PDF keyword extraction failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);

            broadcast(new PdfIndexingFailed(
                $this->sessionId,
                $e->getMessage()
            ));
        } finally {
            // Clean up temporary file
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        }
    }

    public function failed(?Exception $exception): void
    {
        Log::error('PDF keyword extraction job failed', [
            'session_id' => $this->sessionId,
            'error' => $exception?->getMessage(),
        ]);

        broadcast(new PdfIndexingFailed(
            $this->sessionId,
            $exception?->getMessage() ?? 'Unknown error occurred'
        ));
    }
}
