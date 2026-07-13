<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

use Random\Randomizer;

/**
 * Backoff that doubles a base delay of 100 milliseconds on every attempt and spreads it with random jitter.
 *
 * <p>The delay for attempt N is <code>100ms * 2^(N - 1)</code>, adjusted by a uniformly random
 * jitter of up to 30 percent of that value in either direction. The jitter keeps concurrent
 * clients from retrying in lockstep against a recovering dependency.</p>
 */
final readonly class ExponentialBackoff implements Backoff
{
    private const int JITTER_PERCENT = 30;
    private const int BASE_MICROSECONDS = 100000;

    private function __construct(private Randomizer $randomizer)
    {
    }

    /**
     * Creates an ExponentialBackoff from the randomizer that draws the jitter.
     *
     * @param Randomizer $randomizer The randomizer used to draw the jitter of each delay.
     * @return ExponentialBackoff The created instance.
     */
    public static function with(Randomizer $randomizer): ExponentialBackoff
    {
        return new ExponentialBackoff(randomizer: $randomizer);
    }

    public function delayFor(int $attempt): int
    {
        $exponential = (ExponentialBackoff::BASE_MICROSECONDS * (2 ** ($attempt - 1)));
        $spread = intdiv(($exponential * ExponentialBackoff::JITTER_PERCENT), 100);

        return ($exponential + $this->randomizer->getInt(-$spread, $spread));
    }
}
