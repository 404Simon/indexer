<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PdfIndexingCompleted;
use App\Events\PdfIndexingProgress;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Smalot\PdfParser\Parser;

final readonly class PdfKeywordIndexerService
{
    public function __construct(
        private Parser $pdfParser = new Parser,
        private string $defaultSystemPrompt = 'Du bist Informatikexperte und Keyword-Extractor. Wenn dir ein Text gegeben wird, extrahiere bitte alle relevanten Informatikbegriffe, bringe sie in ihre Singularform und gebe sie als Liste zurück. Fokussiere dich auf technische Begriffe, Konzepte, Algorithmen, Programmiersprachen, Frameworks, Tools und andere wichtige Informatikthemen.'
    ) {}

    public function extractPageTexts(string $filePath): Collection
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $pages = $pdf->getPages();

        return collect($pages)
            ->map(fn ($page) => trim($page->getText()))
            ->filter(fn ($text) => $text !== '')
            ->map(fn ($text) => $this->sanitizeText($text))
            ->map(fn ($text) => strlen($text) > 5000 ? substr($text, 0, 5000) : $text)
            ->values();
    }

    public function processDocument(
        string $filePath,
        ?string $systemPrompt = null,
        ?string $sessionId = null
    ): array {
        $systemPrompt ??= $this->defaultSystemPrompt;
        $pageTexts = $this->extractPageTexts($filePath);

        if (empty($pageTexts)) {
            throw new Exception('Keine lesbaren Texte im PDF gefunden.');
        }

        $extractedKeywords = [];
        $totalPages = count($pageTexts);

        foreach ($pageTexts as $pageIndex => $text) {
            $currentPage = $pageIndex + 1;

            if ($sessionId) {
                PdfIndexingProgress::dispatch($sessionId, $currentPage, $totalPages);
            }

            $keywords = $this->extractKeywordsFromText($text, $systemPrompt);

            foreach ($keywords as $keyword) {
                $extractedKeywords[$keyword][] = $currentPage;
            }
        }

        // Clean up and sort results
        $extractedKeywords = collect($extractedKeywords)
            ->map(fn ($pages) => collect($pages)->unique()->sort()->values()->toArray())
            ->sortKeys()
            ->toArray();

        if ($sessionId) {
            PdfIndexingCompleted::dispatch($sessionId, $extractedKeywords);
        }

        return $extractedKeywords;
    }

    public function extractKeywordsFromText(
        string $text,
        ?string $systemPrompt = null
    ): Collection {
        $systemPrompt ??= $this->defaultSystemPrompt;
        $sanitizedText = $this->sanitizeText($text);

        Log::info('Extracting keywords from text', [
            'text_length' => strlen($sanitizedText),
        ]);

        try {
            $schema = new ObjectSchema(
                name: 'keyword_extraction',
                description: 'Extracted keywords from text',
                properties: [
                    new ArraySchema(
                        name: 'keywords',
                        description: 'List of relevant keywords in singular form',
                        items: new StringSchema('keyword', 'A relevant keyword')
                    ),
                ],
                requiredFields: ['keywords']
            );

            $response = Prism::structured()
                ->using(Provider::OpenAI, 'gpt-4o-mini')
                ->withSchema($schema)
                ->withPrompt($systemPrompt."\n\nText to analyze:\n".$sanitizedText)
                ->asStructured();

            return collect($response->structured['keywords'])->map(fn ($k) => Str::lower($k)) ?? [];
        } catch (Exception $e) {
            Log::error('AI keyword extraction failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($sanitizedText),
            ]);

            return [];
        }
    }

    public function generateIndexFile(array $extractedKeywords): string
    {
        return collect($extractedKeywords)
            ->map(fn ($pages, $keyword) => "{$keyword}: [".implode(', ', $pages).']')
            ->implode("\n");
    }

    private function sanitizeText(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', ' ', $text);

        return preg_replace('/\s{2,}/u', ' ', $text);
    }
}
