<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Time\MonotonicClock;
use TinyBlocks\Time\Stopwatch;

/**
 * PSR-18 decorator that retries transient failures with a configurable {@see Backoff}.
 *
 * <p>A network failure or a server error (HTTP 5xx) is retried until the attempt ceiling is
 * reached, sleeping the backoff delay between attempts. A client error (HTTP 4xx) is never
 * retried, and the response is returned as is. Any other failure raised by the decorated
 * client propagates immediately. When the attempts are exhausted, the last response is
 * returned or the last failure is rethrown.</p>
 *
 * <p>Every failed attempt, the final one included, is reported to the configured
 * {@see RetryListener} with its elapsed interval, {@see AttemptOutcome}, request, and
 * attempt number.</p>
 */
final readonly class RetryingClient implements ClientInterface
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

    /**
     * Returns a fluent builder used to assemble a RetryingClient.
     *
     * <p>A PSR-18 client must be supplied through the builder before calling build(),
     * otherwise ClientNotConfigured is raised. Every other collaborator falls back to
     * an opinionated default resolved by the builder.</p>
     *
     * @return RetryingClientBuilder A new, empty builder.
     */
    public static function create(): RetryingClientBuilder
    {
        return new RetryingClientBuilder();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        for ($attemptNumber = 1; true; $attemptNumber++) {
            $stopwatch = Stopwatch::start(clock: $this->clock);

            try {
                $response = $this->client->sendRequest($request);
            } catch (NetworkExceptionInterface $exception) {
                $this->listener->attemptFailed(
                    elapsed: $stopwatch->elapsed(),
                    outcome: AttemptOutcome::fromThrowable(throwable: $exception),
                    request: $request,
                    attemptNumber: $attemptNumber
                );

                if ($attemptNumber >= $this->maxAttempts) {
                    throw $exception;
                }

                $microseconds = $this->backoff->delayFor(attempt: $attemptNumber);
                $this->sleeper->sleep(microseconds: $microseconds);
                continue;
            }

            $outcome = AttemptOutcome::fromStatusCode(statusCode: $response->getStatusCode());

            if (is_null($outcome)) {
                return $response;
            }

            $this->listener->attemptFailed(
                elapsed: $stopwatch->elapsed(),
                outcome: $outcome,
                request: $request,
                attemptNumber: $attemptNumber
            );

            if (!$outcome->isRetryable() || $attemptNumber >= $this->maxAttempts) {
                return $response;
            }

            $microseconds = $this->backoff->delayFor(attempt: $attemptNumber);
            $this->sleeper->sleep(microseconds: $microseconds);
        }
    }
}
