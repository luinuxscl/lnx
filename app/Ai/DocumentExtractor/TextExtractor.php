<?php

namespace App\Ai\DocumentExtractor;

use App\Ai\DocumentExtractor\Exceptions\UnprocessableDocumentException;
use App\Ai\DocumentExtractor\Exceptions\UnsupportedFileTypeException;

class TextExtractor
{
    /**
     * Supported file extensions.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'txt', 'md', 'json', 'csv'];

    /**
     * Extract text and page count from a local document file path.
     *
     * @return array{text: string, page_count: int|null}
     *
     * @throws UnsupportedFileTypeException
     * @throws UnprocessableDocumentException
     */
    public function extract(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new UnprocessableDocumentException('File does not exist or is not readable.');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new UnsupportedFileTypeException("Unsupported file extension [{$extension}]. Allowed: ".implode(', ', self::ALLOWED_EXTENSIONS));
        }

        $maxSizeKb = (int) config('lnx_ai.document_extraction.max_file_size_kb', 10240);
        $fileSizeKb = (int) ceil(filesize($filePath) / 1024);

        if ($fileSizeKb > $maxSizeKb) {
            throw new UnprocessableDocumentException("File size ({$fileSizeKb} KB) exceeds maximum allowed limit ({$maxSizeKb} KB).");
        }

        if ($extension === 'pdf') {
            return $this->extractPdf($filePath);
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new UnprocessableDocumentException('Unable to read text file content.');
        }

        $trimmed = trim($content);

        if ($trimmed === '') {
            throw new UnprocessableDocumentException('Document is empty.');
        }

        return [
            'text' => $trimmed,
            'page_count' => 1,
        ];
    }

    /**
     * Extract text and count pages for a PDF file.
     *
     * @return array{text: string, page_count: int}
     *
     * @throws UnprocessableDocumentException
     */
    protected function extractPdf(string $filePath): array
    {
        $maxPages = (int) config('lnx_ai.document_extraction.max_pages', 20);
        $pageCount = $this->countPdfPages($filePath);

        if ($pageCount > $maxPages) {
            throw new UnprocessableDocumentException("Document exceeds maximum allowed pages ({$pageCount} > {$maxPages}).");
        }

        $text = $this->extractPdfTextCommand($filePath) ?? $this->extractPdfTextNative($filePath);
        $trimmed = trim($text);

        if ($trimmed === '') {
            throw new UnprocessableDocumentException('Scanned or unreadable document: OCR is not supported.');
        }

        return [
            'text' => $trimmed,
            'page_count' => $pageCount,
        ];
    }

    /**
     * Count pages in a PDF.
     */
    protected function countPdfPages(string $filePath): int
    {
        // Attempt pdfinfo command if available
        if (function_exists('exec')) {
            $output = [];
            $returnVar = -1;
            @exec('pdfinfo '.escapeshellarg($filePath).' 2>/dev/null', $output, $returnVar);

            if ($returnVar === 0) {
                foreach ($output as $line) {
                    if (preg_match('/^Pages:\s*(\d+)/i', trim($line), $matches)) {
                        return max(1, (int) $matches[1]);
                    }
                }
            }
        }

        // Fallback: inspect raw PDF content for /Type /Page
        $content = @file_get_contents($filePath);

        if ($content !== false) {
            $count = preg_match_all('/\/Type\s*\/Page\b/', $content);
            if ($count > 0) {
                return $count;
            }
        }

        return 1;
    }

    /**
     * Attempt pdftotext command line extraction.
     */
    protected function extractPdfTextCommand(string $filePath): ?string
    {
        if (! function_exists('exec')) {
            return null;
        }

        $output = [];
        $returnVar = -1;
        @exec('pdftotext -layout '.escapeshellarg($filePath).' - 2>/dev/null', $output, $returnVar);

        if ($returnVar === 0 && ! empty($output)) {
            return implode("\n", $output);
        }

        return null;
    }

    /**
     * Native basic text extraction fallback for uncompressed/lightly-compressed PDF text streams.
     */
    protected function extractPdfTextNative(string $filePath): string
    {
        $content = @file_get_contents($filePath);

        if ($content === false) {
            return '';
        }

        $text = '';

        // Extract BT...ET (Begin Text ... End Text) blocks
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
            foreach ($matches[1] as $block) {
                // Find string literal tokens (text) or array tokens [(text1)(text2)]
                if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $block, $tMatches)) {
                    foreach ($tMatches[1] as $str) {
                        $text .= $this->decodePdfString($str).' ';
                    }
                } elseif (preg_match_all('/\[\s*(.*?)\s*\]\s*TJ/s', $block, $tjMatches)) {
                    foreach ($tjMatches[1] as $arrayContent) {
                        if (preg_match_all('/\((.*?)\)/s', $arrayContent, $strMatches)) {
                            foreach ($strMatches[1] as $str) {
                                $text .= $this->decodePdfString($str);
                            }
                            $text .= ' ';
                        }
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Basic unescaping of PDF literal strings.
     */
    protected function decodePdfString(string $pdfString): string
    {
        return str_replace(['\\\\', '\(', '\)', '\n', '\r', '\t'], ['\\', '(', ')', "\n", "\r", "\t"], $pdfString);
    }
}
