<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Psr\Http\Message\RequestInterface;
use TinyBlocks\Http\Client\Resilience\AttemptOutcome;
use TinyBlocks\Http\Client\Resilience\RetryListener;
use TinyBlocks\Time\Elapsed;

final class RecordingListener implements RetryListener
{
    private array $failures = [];

    public function failures(): array
    {
        return $this->failures;
    }

    public function attemptFailed(
        Elapsed $elapsed,
        AttemptOutcome $outcome,
        RequestInterface $request,
        int $attemptNumber
    ): void {
        $this->failures[] = [
            'outcome'               => $outcome,
            'request'               => $request,
            'attemptNumber'         => $attemptNumber,
            'elapsedInMilliseconds' => $elapsed->toMilliseconds()
        ];
    }
}
