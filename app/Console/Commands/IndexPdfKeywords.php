<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PdfKeywordIndexerService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

        if (! $this->validateInputs($filePath, $divider)) {
            return self::FAILURE;
        }

        $this->displayProcessingInfo($filePath, $divider);

        try {
            $pageTexts = $indexerService->extractPageTexts($filePath);

            if ($pageTexts->isEmpty()) {
                $this->error('No readable text found in PDF');

                return self::FAILURE;
            }

            $this->info("Found {$pageTexts->count()} pages with text content");

            $progressBar = $this->output->createProgressBar($pageTexts->count());
            $progressBar->start();

            $extractedKeywords = $this->processPages(
                $pageTexts,
                $indexerService,
                $customPrompt,
                $divider,
                $progressBar
            );

            $progressBar->finish();
            $this->newLine(2);

            $this->displayResults($extractedKeywords, $pageTexts->count(), $divider);

            $content = $this->formatOutput($extractedKeywords, $format, $indexerService);

            $this->handleOutput($content, $outputPath);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->newLine();
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function validateInputs(string $filePath, ?string $divider): bool
    {
        return collect([
            fn () => File::exists($filePath) ?: $this->error("File not found: {$filePath}"),
            fn () => str_ends_with(strtolower($filePath), '.pdf') ?: $this->error('File must be a PDF document'),
            fn () => $divider === null || (int) $divider >= 1 ?: $this->error('Divider must be a positive integer'),
        ])->every(fn ($validator) => $validator());
    }

    private function displayProcessingInfo(string $filePath, ?string $divider): void
    {
        $this->info("Processing PDF: {$filePath}");

        when($divider, fn () => $this->info(
            "Using page divider: {$divider} (every {$divider} original pages = 1 indexed page)"
        ));

        $this->newLine();
    }

    private function processPages(
        Collection $pageTexts,
        PdfKeywordIndexerService $indexerService,
        ?string $customPrompt,
        ?string $divider,
        $progressBar
    ): array {
        $dividerInt = $divider ? (int) $divider : null;

        return $pageTexts
            ->flatMap(function (string $text, int $pageIndex) use (
                $indexerService,
                $customPrompt,
                $dividerInt,
                $progressBar
            ) {
                $cacheKey = 'keywords_'.md5($text.$customPrompt);
                $keywords = Cache::rememberForever(
                    $cacheKey,
                    fn () => $indexerService->extractKeywordsFromText($text, $customPrompt)
                );

                $originalPageNumber = $pageIndex + 1;
                $adjustedPageNumber = $dividerInt
                    ? (int) ceil($originalPageNumber / $dividerInt)
                    : $originalPageNumber;

                $progressBar->advance();

                return collect($keywords)->map(fn ($keyword) => [
                    'keyword' => $keyword,
                    'page' => $adjustedPageNumber,
                ]);
            })
            ->groupBy('keyword')
            ->map(fn ($group) => $group->pluck('page')->unique()->sort()->values()->toArray())
            ->sortKeys()
            ->toArray();
    }

    private function displayResults(array $extractedKeywords, int $totalPages, ?string $divider): void
    {
        $keywordCount = count($extractedKeywords);
        $this->info("Extraction completed! Found {$keywordCount} unique keywords.");

        when($divider, function () use ($totalPages, $divider) {
            $finalPageCount = (int) ceil($totalPages / (int) $divider);
            $this->info("Original pages: {$totalPages} → Indexed pages: {$finalPageCount}");
        });
    }

    private function formatOutput(
        array $extractedKeywords,
        string $format,
        PdfKeywordIndexerService $indexerService
    ): string {
        return match ($format) {
            'json' => json_encode(
                $extractedKeywords,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            'txt' => $indexerService->generateIndexFile($extractedKeywords),
            default => throw new Exception("Unsupported format: {$format}"),
        };
    }

    private function handleOutput(string $content, ?string $outputPath): void
    {
        if ($outputPath) {
            File::put($outputPath, $content);
            $this->info("Index saved to: {$outputPath}");
        } else {
            $this->newLine();
            $this->line($content);
        }
    }
}
