<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client\Resilience;

use Psr\Http\Message\RequestInterface;
use TinyBlocks\Http\Client\Resilience\AttemptOutcome;
use TinyBlocks\Http\Client\Resilience\RetryListener;
use TinyBlocks\Time\Elapsed;

final readonly class IgnoringRetryListener implements RetryListener
{
    public function attemptFailed(
        Elapsed $elapsed,
        AttemptOutcome $outcome,
        RequestInterface $request,
        int $attemptNumber
    ): void {
    }
}
