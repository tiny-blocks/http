<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use TinyBlocks\Http\Client\Resilience\Sleeper;

final class RecordingSleeper implements Sleeper
{
    private array $sleeps = [];

    public function sleep(int $microseconds): void
    {
        $this->sleeps[] = $microseconds;
    }

    public function sleeps(): array
    {
        return $this->sleeps;
    }
}
