<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Internal\Client\Resilience\RetryLoop;
use TinyBlocks\Time\MonotonicClock;

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
    private RetryLoop $loop;

    public function __construct(
        MonotonicClock $clock,
        ClientInterface $client,
        Backoff $backoff,
        Sleeper $sleeper,
        RetryListener $listener,
        int $maxAttempts
    ) {
        $this->loop = new RetryLoop(
            clock: $clock,
            client: $client,
            backoff: $backoff,
            sleeper: $sleeper,
            listener: $listener,
            maxAttempts: $maxAttempts
        );
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
        return $this->loop->run(request: $request);
    }
}
