<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Resilience;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Client\Resilience\FixedDelay;

final class FixedDelayTest extends TestCase
{
    #[DataProvider('attemptScenarios')]
    public function testDelayForWhenAnyAttemptIsGivenThenTheDelayIsTheConfiguredValue(int $attempt): void
    {
        /** @Given a fixed delay of half a second */
        $backoff = FixedDelay::ofMicroseconds(microseconds: 500000);

        /** @When the delay for the attempt is computed */
        $delay = $backoff->delayFor(attempt: $attempt);

        /** @Then the delay is the configured half a second */
        self::assertSame(500000, $delay);
    }

    public static function attemptScenarios(): array
    {
        return [
            'First attempt'   => ['attempt' => 1],
            'Fifth attempt'   => ['attempt' => 5],
            'Twelfth attempt' => ['attempt' => 12]
        ];
    }
}
