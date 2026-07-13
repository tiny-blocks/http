<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

/**
 * Backoff that waits the same delay before every retry attempt.
 */
final readonly class FixedDelay implements Backoff
{
    private function __construct(private int $microseconds)
    {
    }

    /**
     * Creates a FixedDelay from a delay expressed in microseconds.
     *
     * @param int $microseconds The delay applied before every retry attempt, in microseconds.
     * @return FixedDelay The created instance.
     */
    public static function ofMicroseconds(int $microseconds): FixedDelay
    {
        return new FixedDelay(microseconds: $microseconds);
    }

    public function delayFor(int $attempt): int
    {
        return $this->microseconds;
    }
}
