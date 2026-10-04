<?php

namespace App\Ai;

use App\Ai\Exceptions\AiNotConfiguredException;
use App\Ai\Exceptions\AiQuotaExceededException;
use App\Ai\Exceptions\ModelNotAllowedException;
use App\Ai\Exceptions\UnsupportedCapabilityException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

/**
 * Maps provider/SDK failures to a small, secret-free category.
 *
 * The category is what the UI reports; raw provider messages are never stored
 * or shown, so a credential or response body cannot leak through an error.
 */
class AiErrorCategorizer
{
    public const OK = 'ok';

    public const NOT_CONFIGURED = 'not_configured';

    public const AUTHENTICATION = 'authentication';

    public const CREDITS = 'credits';

    public const MODEL = 'model';

    public const TIMEOUT = 'timeout';

    public const UNAVAILABLE = 'unavailable';

    public const RATE_LIMITED = 'rate_limited';

    public const LOCAL_QUOTA = 'quota';

    public const UNKNOWN = 'unknown';

    public function categorize(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof AiNotConfiguredException => self::NOT_CONFIGURED,
            $exception instanceof ModelNotAllowedException || $exception instanceof UnsupportedCapabilityException => self::MODEL,
            $exception instanceof AiQuotaExceededException => self::LOCAL_QUOTA,
            $exception instanceof InsufficientCreditsException => self::CREDITS,
            $exception instanceof RateLimitedException => self::RATE_LIMITED,
            $exception instanceof ProviderOverloadedException => self::UNAVAILABLE,
            $exception instanceof ProviderConnectionException => $this->categorizeConnection($exception),
            $exception instanceof RequestException => $this->categorizeStatus($exception->response->status()),
            default => self::UNKNOWN,
        };
    }

    /**
     * Distinguish a timeout from other connectivity failures.
     */
    protected function categorizeConnection(ProviderConnectionException $exception): string
    {
        $previous = $exception->getPrevious();

        if ($previous instanceof ConnectionException && $this->isTimeout($previous)) {
            return self::TIMEOUT;
        }

        return self::UNAVAILABLE;
    }

    /**
     * Whether an HTTP client connection exception was a timeout.
     */
    protected function isTimeout(ConnectionException $exception): bool
    {
        if ($exception->getCode() === 28) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'timeout');
    }

    /**
     * Map an HTTP status returned by the provider to a category.
     */
    protected function categorizeStatus(?int $status): string
    {
        return match ($status) {
            400, 404, 422 => self::MODEL,
            401, 403 => self::AUTHENTICATION,
            402 => self::CREDITS,
            408, 504 => self::TIMEOUT,
            429 => self::RATE_LIMITED,
            500, 502, 503, 520, 522, 524 => self::UNAVAILABLE,
            default => self::UNKNOWN,
        };
    }
}
