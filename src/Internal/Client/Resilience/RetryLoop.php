<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client\Resilience;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Client\Resilience\AttemptOutcome;
use TinyBlocks\Http\Client\Resilience\Backoff;
use TinyBlocks\Http\Client\Resilience\RetryListener;
use TinyBlocks\Http\Client\Resilience\Sleeper;
use TinyBlocks\Time\MonotonicClock;
use TinyBlocks\Time\Stopwatch;

final readonly class RetryLoop
{
    public function __construct(
        private MonotonicClock $clock,
        private ClientInterface $client,
        private Backoff $backoff,
        private Sleeper $sleeper,
        private RetryListener $listener,
        private int $maxAttempts
    ) {
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        for ($attemptNumber = 1; true; $attemptNumber++) {
            $response = $this->attempt(request: $request, attemptNumber: $attemptNumber);

            if (!is_null($response)) {
                return $response;
            }

            $this->waitBefore(attemptNumber: $attemptNumber);
        }
    }

    private function stops(AttemptOutcome $outcome, int $attemptNumber): bool
    {
        return !$outcome->isRetryable() || $attemptNumber >= $this->maxAttempts;
    }

    private function attempt(RequestInterface $request, int $attemptNumber): ?ResponseInterface
    {
        $stopwatch = Stopwatch::start(clock: $this->clock);

        try {
            $response = $this->client->sendRequest($request);
        } catch (NetworkExceptionInterface $exception) {
            $this->recordFailure(
                outcome: AttemptOutcome::fromThrowable(throwable: $exception),
                request: $request,
                stopwatch: $stopwatch,
                attemptNumber: $attemptNumber
            );

            if ($attemptNumber >= $this->maxAttempts) {
                throw $exception;
            }

            return null;
        }

        $outcome = AttemptOutcome::fromStatusCode(statusCode: $response->getStatusCode());

        if (is_null($outcome)) {
            return $response;
        }

        $this->recordFailure(
            outcome: $outcome,
            request: $request,
            stopwatch: $stopwatch,
            attemptNumber: $attemptNumber
        );

        return $this->stops(outcome: $outcome, attemptNumber: $attemptNumber) ? $response : null;
    }

    private function waitBefore(int $attemptNumber): void
    {
        $microseconds = $this->backoff->delayFor(attempt: $attemptNumber);
        $this->sleeper->sleep(microseconds: $microseconds);
    }

    private function recordFailure(
        AttemptOutcome $outcome,
        RequestInterface $request,
        Stopwatch $stopwatch,
        int $attemptNumber
    ): void {
        $this->listener->attemptFailed(
            elapsed: $stopwatch->elapsed(),
            outcome: $outcome,
            request: $request,
            attemptNumber: $attemptNumber
        );
    }
}
