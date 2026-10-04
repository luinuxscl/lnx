<?php

namespace App\Ai\Knowledge;

class TextChunker
{
    /**
     * Chunk text into overlapping segments, respecting paragraph/line boundaries.
     *
     * @return list<string>
     */
    public function chunk(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $chunkSize) {
            return [$text];
        }

        $paragraphs = explode("\n\n", $text);
        $chunks = [];
        $currentChunk = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            // If a single paragraph exceeds chunk size, split it by sentences or fixed length
            if (mb_strlen($paragraph) > $chunkSize) {
                if (! empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }

                $subChunks = $this->splitLongText($paragraph, $chunkSize, $overlap);
                foreach ($subChunks as $sub) {
                    $chunks[] = $sub;
                }

                continue;
            }

            if (empty($currentChunk)) {
                $currentChunk = $paragraph;
            } elseif (mb_strlen($currentChunk) + mb_strlen($paragraph) + 2 <= $chunkSize) {
                $currentChunk .= "\n\n".$paragraph;
            } else {
                $chunks[] = trim($currentChunk);

                // Start next chunk with overlap from the end of currentChunk if possible
                $overlapText = mb_substr($currentChunk, max(0, mb_strlen($currentChunk) - $overlap));
                $currentChunk = $overlapText."\n\n".$paragraph;
            }
        }

        if (! empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        return array_values(array_filter($chunks, fn ($c) => trim($c) !== ''));
    }

    /**
     * Split a long text segment that exceeds max chunk size into fixed-size chunks with overlap.
     *
     * @return list<string>
     */
    protected function splitLongText(string $text, int $chunkSize, int $overlap): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $slice = mb_substr($text, $start, $chunkSize);
            $chunks[] = trim($slice);

            $step = $chunkSize - $overlap;
            if ($step <= 0) {
                $step = $chunkSize;
            }

            $start += $step;
        }

        return $chunks;
    }
}
