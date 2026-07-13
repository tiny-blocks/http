<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

/**
 * Delay policy applied between retry attempts.
 *
 * <p>Implementations compute how long a {@see RetryingClient} waits before the next attempt.
 * Built-in: {@see FixedDelay} (constant delay) and {@see ExponentialBackoff} (doubling delay
 * with random jitter).</p>
 */
interface Backoff
{
    /**
     * Returns the delay applied before the next attempt, in microseconds.
     *
     * @param int $attempt The number of the attempt that has just failed, starting at one.
     * @return int The delay applied before the next attempt, in microseconds.
     */
    public function delayFor(int $attempt): int;
}
