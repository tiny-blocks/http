<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Resilience;

use Psr\Http\Client\ClientInterface;
use Random\Randomizer;
use TinyBlocks\Http\Exceptions\ClientNotConfigured;
use TinyBlocks\Http\Internal\Client\Resilience\IgnoringRetryListener;
use TinyBlocks\Http\Internal\Client\Resilience\SystemSleeper;
use TinyBlocks\Time\MonotonicClock;
use TinyBlocks\Time\SystemMonotonicClock;

/**
 * Fluent builder that assembles a {@see RetryingClient} around a required PSR-18 client.
 *
 * <p>Every collaborator except the client falls back to an opinionated default resolved at
 * build time: an {@see ExponentialBackoff} with random jitter, the system monotonic clock,
 * the system sleeper, and a listener that ignores failures. The attempt ceiling defaults
 * to three.</p>
 */
final class RetryingClientBuilder
{
    private ?MonotonicClock $clock = null;
    private ?ClientInterface $client = null;
    private ?Backoff $backoff = null;
    private ?Sleeper $sleeper = null;
    private ?RetryListener $listener = null;
    private int $maxAttempts = 3;

    /**
     * Builds a RetryingClient from the configured client and collaborators.
     *
     * <p>Collaborators left unconfigured fall back to the opinionated defaults. The attempt
     * ceiling counts the first attempt, so values below two mean a single attempt.</p>
     *
     * @return RetryingClient The configured client instance.
     * @throws ClientNotConfigured If no PSR-18 client was configured.
     */
    public function build(): RetryingClient
    {
        if (is_null($this->client)) {
            throw ClientNotConfigured::create();
        }

        return new RetryingClient(
            clock: $this->clock ?? new SystemMonotonicClock(),
            client: $this->client,
            backoff: $this->backoff ?? ExponentialBackoff::with(randomizer: new Randomizer()),
            sleeper: $this->sleeper ?? new SystemSleeper(),
            listener: $this->listener ?? new IgnoringRetryListener(),
            maxAttempts: $this->maxAttempts
        );
    }

    /**
     * Sets the delay policy applied between attempts and returns the builder.
     *
     * @param Backoff $backoff The delay policy applied between attempts.
     * @return RetryingClientBuilder The builder instance for fluent configuration.
     */
    public function withBackoff(Backoff $backoff): RetryingClientBuilder
    {
        $this->backoff = $backoff;
        return $this;
    }

    /**
     * Sets the PSR-18 client that performs each attempt and returns the builder.
     *
     * @param ClientInterface $client The PSR-18 client that performs each attempt.
     * @return RetryingClientBuilder The builder instance for fluent configuration.
     */
    public function withClient(ClientInterface $client): RetryingClientBuilder
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Sets the monotonic clock measuring each attempt and returns the builder.
     *
     * @param MonotonicClock $clock The monotonic clock measuring each attempt.
     * @return RetryingClientBuilder The builder instance for fluent configuration.
     */
    public function withClock(MonotonicClock $clock): RetryingClientBuilder
    {
        $this->clock = $clock;
        return $this;
    }

    /**
     * Sets the listener notified of every failed attempt and returns the builder.
     *
     * @param RetryListener $listener The listener notified of every failed attempt.
     * @return RetryingClientBuilder The builder instance for fluent configuration.
     */
    public function withListener(RetryListener $listener): RetryingClientBuilder
    {
        $this->listener = $listener;
        return $this;
    }

    /**
     * Sets the attempt ceiling and returns the builder.
     *
     * <p>The ceiling counts the first attempt, so a value of two means one retry. Values
     * below two mean a single attempt, and no validation is applied.</p>
     *
     * @param int $maxAttempts The attempt ceiling, counting the first attempt.
     * @return RetryingClientBuilder The builder instance for fluent configuration.
     */
    public function withMaxAttempts(int $maxAttempts): RetryingClientBuilder
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    /**
     * Sets the suspension applied between attempts and returns the builder.
     *
     * @param Sleeper $sleeper The suspension applied between attempts.
     * @return RetryingClientBuilder The builder instance for fluent configuration.
     */
    public function withSleeper(Sleeper $sleeper): RetryingClientBuilder
    {
        $this->sleeper = $sleeper;
        return $this;
    }
}
