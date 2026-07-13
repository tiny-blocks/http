<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

use Throwable;
use TinyBlocks\Http\Code;

/**
 * Classification of a failed HTTP attempt, driving whether a retry is worthwhile.
 */
enum AttemptOutcome: string
{
    case TIMEOUT = 'timeout';
    case CLIENT_ERROR = '4xx';
    case SERVER_ERROR = '5xx';
    case CONNECTION_RESET = 'connection_reset';

    /**
     * Creates an AttemptOutcome from a transport failure.
     *
     * <p>A failure whose message mentions a timeout classifies as {@see AttemptOutcome::TIMEOUT}.
     * Every other transport failure classifies as {@see AttemptOutcome::CONNECTION_RESET}.</p>
     *
     * @param Throwable $throwable The transport failure to classify.
     * @return AttemptOutcome The classification of the failure.
     */
    public static function fromThrowable(Throwable $throwable): AttemptOutcome
    {
        $message = strtolower($throwable->getMessage());
        $isTimeout = str_contains($message, 'timed out') || str_contains($message, 'timeout');

        return $isTimeout ? AttemptOutcome::TIMEOUT : AttemptOutcome::CONNECTION_RESET;
    }

    /**
     * Creates an AttemptOutcome from an HTTP status code, or null when the status is not a failure.
     *
     * <p>The timeout statuses recognized by {@see Code::isTimeout()} (408 Request Timeout and 504
     * Gateway Timeout) classify as {@see AttemptOutcome::TIMEOUT}, so they stay retryable and the
     * failure log carries the timeout vocabulary. The remaining floors are open-ended on purpose:
     * non-RFC codes above the {@see Code} ranges (a 599 from a proxy, for example) still classify
     * as failures and stay retryable.</p>
     *
     * @param int $statusCode The HTTP status code to classify.
     * @return AttemptOutcome|null The classification of the status, or null below the client error floor.
     */
    public static function fromStatusCode(int $statusCode): ?AttemptOutcome
    {
        $code = Code::tryFrom($statusCode);

        return match (true) {
            !is_null($code) && $code->isTimeout()             => AttemptOutcome::TIMEOUT,
            $statusCode >= Code::INTERNAL_SERVER_ERROR->value => AttemptOutcome::SERVER_ERROR,
            $statusCode >= Code::BAD_REQUEST->value           => AttemptOutcome::CLIENT_ERROR,
            default                                           => null
        };
    }

    /**
     * Tells whether a retry is worthwhile after this outcome.
     *
     * @return bool True for every outcome except {@see AttemptOutcome::CLIENT_ERROR}.
     */
    public function isRetryable(): bool
    {
        return $this !== AttemptOutcome::CLIENT_ERROR;
    }
}
