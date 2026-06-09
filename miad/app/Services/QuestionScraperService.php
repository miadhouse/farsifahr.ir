<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuestionScraperService
{
    private array $httpHeaders = [
        'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
    ];

    // ─────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────

    /**
     * Scrape führerschein-bestehen.de for a given question number and text.
     */
    public function scrape(string $questionNumber, string $questionText = ''): array
    {
        $url = $this->buildDirectUrl($questionNumber, $questionText);
        Log::info('[QuestionScraper] Fetching Direct URL: ' . $url);

        $response = Http::withHeaders($this->httpHeaders)
            ->timeout(20)
            ->get($url);

        if (! $response->successful() || str_contains($response->body(), '404 - Seite nicht gefunden')) {
            throw new \RuntimeException("Could not find the question page at: {$url}");
        }

        return $this->parseHtml($response->body());
    }

    /**
     * Build the direct Erklaerungen URL based on site's slug pattern.
     */
    private function buildDirectUrl(string $number, string $text): string
    {
        // 1. Process DashCode (lower-case and dots to dashes)
        $dashCode = str_replace('.', '-', mb_strtolower(trim($number), 'UTF-8'));
        
        // 2. Process Slug
        $slug = mb_strtolower(trim($text), 'UTF-8');
        $slug = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $slug);
        
        // Replace all non-alphanumeric chars with a dash
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
        
        // Trim and collapse multiple dashes
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');
        
        return "https://www.fuehrerschein-bestehen.de/Erklaerungen/{$slug}-{$dashCode}";
    }

    // ─────────────────────────────────────────────
    // HTML parsing
    // ─────────────────────────────────────────────

    /**
     * Parse the HTML page and extract question info and per-answer explanations.
     */
    public function parseHtml(string $html): array
    {
        $result = [
            'question_info' => null,
            'answers'       => [],
        ];

        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        // Convert charset so umlauts survive
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // ── 1. Question-level explanation ──────────────────────────────
        // We look for an explanation that is NOT inside an answer item
        $questionInfoNodes = $xpath->query(
            '//*[@id="fsb-fragentexte"]'
            . '/div[contains(@class,"fsb-erklaerung")]'
            . '//div[contains(@class,"fsb-erklaerung__text")]'
        );

        // Fallback: search anywhere inside fsb-fragentexte but take the first one
        if (!$questionInfoNodes || $questionInfoNodes->length === 0) {
            $questionInfoNodes = $xpath->query(
                '//*[@id="fsb-fragentexte"]//div[contains(@class,"fsb-erklaerung")]//div[contains(@class,"fsb-erklaerung__text")]'
            );
        }

        if ($questionInfoNodes && $questionInfoNodes->length > 0) {
            $text = $this->cleanText($questionInfoNodes->item(0)->textContent);
            if ($text !== '') {
                $result['question_info'] = $text;
            }
        }

        // ── 2. Per-answer data ─────────────────────────────────────────
        $answerItems = $xpath->query(
            '//*[@id="fsb-fragentexte"]/div[contains(@class,"fsb-answer-item")]'
        );

        if ($answerItems) {
            foreach ($answerItems as $item) {
                $answerText = null;
                $answerInfo = null;

                $textNodes = $xpath->query(
                    './/div[contains(@class,"fsb-antwort__text")]',
                    $item
                );
                if ($textNodes && $textNodes->length > 0) {
                    $answerText = $this->cleanText($textNodes->item(0)->textContent);
                }

                $infoNodes = $xpath->query(
                    './/div[contains(@class,"fsb-erklaerung")]'
                    . '//div[contains(@class,"fsb-erklaerung__text")]',
                    $item
                );
                if ($infoNodes && $infoNodes->length > 0) {
                    $infoText = $this->cleanText($infoNodes->item(0)->textContent);
                    if ($infoText !== '') {
                        $answerInfo = $infoText;
                    }
                }

                if ($answerText) {
                    $result['answers'][] = [
                        'text' => $answerText,
                        'info' => $answerInfo,
                    ];
                }
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function cleanText(string $raw): string
    {
        $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/', ' ', trim($text));
    }

    public function translate(string $text, string $from = 'de', string $to = 'fa'): string
    {
        if (empty(trim($text))) return '';

        try {
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$from}&tl={$to}&dt=t&q=" . urlencode($text);
            $response = Http::timeout(20)->get($url);

            if ($response->successful()) {
                $result = $response->json();
                $translation = "";
                if (isset($result[0])) {
                    foreach ($result[0] as $segment) {
                        $translation .= $segment[0] ?? "";
                    }
                    return trim($translation);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[QuestionScraper] Translation failed: ' . $e->getMessage());
        }

        return '';
    }

    public function textsMatch(string $dbText, string $webText): bool
    {
        $a = $this->normalizeForMatch($dbText);
        $b = $this->normalizeForMatch($webText);

        if ($a === '' || $b === '') return false;
        if ($a === $b) return true;
        if (str_contains($a, $b) || str_contains($b, $a)) return true;

        similar_text($a, $b, $percent);
        return $percent >= 85.0;
    }

    private function normalizeForMatch(string $text): string
    {
        $text = strip_tags($text);
        $text = $this->cleanText($text);
        $text = mb_strtolower($text, 'UTF-8');
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
    }
}
