<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PdfKeywordIndexerService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class IndexPdfKeywords extends Command
{
    protected $signature = 'pdf:index
                           {file : Path to the PDF file}
                           {--output= : Output file path (optional)}
                           {--prompt= : Custom system prompt (optional)}
                           {--format=txt : Output format (txt|json)}
                           {--divider= : Page divider (e.g., 9 means 9 original pages = 1 indexed page)}';

    protected $description = 'Extract and index keywords from a PDF document using AI';

    public function handle(PdfKeywordIndexerService $indexerService): int
    {
        $filePath = $this->argument('file');
        $outputPath = $this->option('output');
        $customPrompt = $this->option('prompt');
        $format = $this->option('format');
        $divider = $this->option('divider');

        if (! File::exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        if (! str_ends_with(strtolower($filePath), '.pdf')) {
            $this->error('File must be a PDF document');

            return self::FAILURE;
        }

        // Validate divider option
        if ($divider !== null) {
            $divider = (int) $divider;
            if ($divider < 1) {
                $this->error('Divider must be a positive integer');

                return self::FAILURE;
            }
        }

        $this->info("Processing PDF: {$filePath}");
        if ($divider) {
            $this->info("Using page divider: {$divider} (every {$divider} original pages = 1 indexed page)");
        }
        $this->newLine();

        try {
            $pageTexts = $indexerService->extractPageTexts($filePath);
            $totalPages = count($pageTexts);

            if ($totalPages === 0) {
                $this->error('No readable text found in PDF');

                return self::FAILURE;
            }

            $this->info("Found {$totalPages} pages with text content");

            $progressBar = $this->output->createProgressBar($totalPages);
            $progressBar->start();

            $extractedKeywords = [];

            foreach ($pageTexts as $pageIndex => $text) {
                $keywords = $indexerService->extractKeywordsFromText($text, $customPrompt);

                foreach ($keywords as $keyword) {
                    $originalPageNumber = $pageIndex + 1;
                    $adjustedPageNumber = $divider
                        ? (int) ceil($originalPageNumber / $divider)
                        : $originalPageNumber;

                    $extractedKeywords[$keyword][] = $adjustedPageNumber;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // cleanup
            $extractedKeywords = collect($extractedKeywords)
                ->map(fn ($pages) => collect($pages)->unique()->sort()->values()->toArray())
                ->sortKeys()
                ->toArray();

            $keywordCount = count($extractedKeywords);
            $this->info("Extraction completed! Found {$keywordCount} unique keywords.");

            if ($divider) {
                $finalPageCount = $divider ? (int) ceil($totalPages / $divider) : $totalPages;
                $this->info("Original pages: {$totalPages} → Indexed pages: {$finalPageCount}");
            }

            $content = match ($format) {
                'json' => json_encode($extractedKeywords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'txt' => $indexerService->generateIndexFile($extractedKeywords),
                default => throw new Exception("Unsupported format: {$format}")
            };

            if ($outputPath) {
                File::put($outputPath, $content);
                $this->info("Index saved to: {$outputPath}");
            } else {
                $this->newLine();
                $this->line($content);
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->newLine();
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
