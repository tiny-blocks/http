<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

use Psr\Http\Message\RequestInterface;
use TinyBlocks\Time\Elapsed;

/**
 * Listener notified of the failed attempts made by a {@see RetryingClient}.
 *
 * <p>Notified once for every failed attempt, including the final one when the attempts are
 * exhausted. Successful attempts are never reported.</p>
 */
interface RetryListener
{
    /**
     * Receives the notification of a failed attempt.
     *
     * @param Elapsed $elapsed The elapsed interval measured for the failed attempt.
     * @param AttemptOutcome $outcome The classification of the failure.
     * @param RequestInterface $request The request whose attempt failed.
     * @param int $attemptNumber The number of the failed attempt, starting at one.
     */
    public function attemptFailed(
        Elapsed $elapsed,
        AttemptOutcome $outcome,
        RequestInterface $request,
        int $attemptNumber
    ): void;
}
