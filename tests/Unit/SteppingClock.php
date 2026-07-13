<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use TinyBlocks\Time\MonotonicClock;

final class SteppingClock implements MonotonicClock
{
    private int $reading;

    public function __construct(int $startingAt, private readonly int $stepInNanoseconds)
    {
        $this->reading = $startingAt;
    }

    public function nanoseconds(): int
    {
        $current = $this->reading;
        $this->reading += $this->stepInNanoseconds;

        return $current;
    }
}
