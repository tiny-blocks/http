<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Resilience;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
use Test\TinyBlocks\Http\Unit\FixedBytesEngine;
use TinyBlocks\Http\Client\Resilience\ExponentialBackoff;

final class ExponentialBackoffTest extends TestCase
{
    private const string ZERO_BYTES = "\x00\x00\x00\x00\x00\x00\x00\x00";

    #[DataProvider('attemptScenarios')]
    public function testDelayForWhenTheJitterIsAtItsLowerBoundThenTheDelayDoublesEachAttempt(
        int $attempt,
        int $expectedDelay
    ): void {
        /** @Given an exponential backoff whose randomizer always draws the lowest jitter */
        $backoff = ExponentialBackoff::with(
            randomizer: new Randomizer(engine: new FixedBytesEngine(bytes: self::ZERO_BYTES))
        );

        /** @When the delay for the attempt is computed */
        $delay = $backoff->delayFor(attempt: $attempt);

        /** @Then the delay is the exponential base for the attempt minus the full jitter band */
        self::assertSame($expectedDelay, $delay);
    }

    public function testDelayForWhenARealRandomizerDrawsTheJitterThenTheDelayStaysWithinTheBounds(): void
    {
        /** @Given an exponential backoff backed by a real randomizer */
        $backoff = ExponentialBackoff::with(randomizer: new Randomizer());

        /** @When the delay for the second attempt is computed */
        $delay = $backoff->delayFor(attempt: 2);

        /** @Then the delay stays within thirty percent of two hundred milliseconds */
        self::assertGreaterThanOrEqual(140000, $delay);
        self::assertLessThanOrEqual(260000, $delay);
    }

    public static function attemptScenarios(): array
    {
        return [
            'Attempt one is 100ms minus 30 percent'   => ['attempt' => 1, 'expectedDelay' => 70000],
            'Attempt two is 200ms minus 30 percent'   => ['attempt' => 2, 'expectedDelay' => 140000],
            'Attempt three is 400ms minus 30 percent' => ['attempt' => 3, 'expectedDelay' => 280000]
        ];
    }
}
