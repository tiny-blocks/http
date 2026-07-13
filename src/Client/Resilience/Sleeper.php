<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

/**
 * Suspension of the current execution for a span of time.
 */
interface Sleeper
{
    /**
     * Suspends the current execution for the given number of microseconds.
     *
     * @param int $microseconds The number of microseconds to suspend the execution.
     */
    public function sleep(int $microseconds): void;
}
