<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client\Resilience;

use TinyBlocks\Http\Client\Resilience\Sleeper;

final readonly class SystemSleeper implements Sleeper
{
    public function sleep(int $microseconds): void
    {
        usleep($microseconds);
    }
}
