<?php

namespace App\Ai\OpenRouter;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * A read-through stream decorator that keeps every byte read from the source.
 *
 * The SDK parses SSE byte by byte, so recording on read preserves streaming
 * while leaving the raw body available for cost extraction once the step ends.
 */
class RecordingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private string $recorded = '';

    /**
     * Read a chunk from the source, appending it to the recording.
     */
    public function read(int $length): string
    {
        $chunk = $this->stream->read($length);

        $this->recorded .= $chunk;

        return $chunk;
    }

    /**
     * The bytes read from the source so far.
     */
    public function recorded(): string
    {
        return $this->recorded;
    }
}
