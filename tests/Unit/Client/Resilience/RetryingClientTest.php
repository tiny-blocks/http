<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Resilience;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Random\Randomizer;
use Test\TinyBlocks\Http\Unit\FixedBytesEngine;
use Test\TinyBlocks\Http\Unit\PsrClientException;
use Test\TinyBlocks\Http\Unit\PsrNetworkException;
use Test\TinyBlocks\Http\Unit\RecordingListener;
use Test\TinyBlocks\Http\Unit\RecordingSleeper;
use Test\TinyBlocks\Http\Unit\ScriptedClient;
use Test\TinyBlocks\Http\Unit\SteppingClock;
use TinyBlocks\Http\Client\Resilience\AttemptOutcome;
use TinyBlocks\Http\Client\Resilience\ExponentialBackoff;
use TinyBlocks\Http\Client\Resilience\FixedDelay;
use TinyBlocks\Http\Client\Resilience\RetryingClient;
use TinyBlocks\Http\Exceptions\ClientNotConfigured;

final class RetryingClientTest extends TestCase
{
    private const string URL = 'https://api.example.com/health';
    private const string ZERO_BYTES = "\x00\x00\x00\x00\x00\x00\x00\x00";

    private RetryingClient $client;
    private ScriptedClient $network;
    private RecordingSleeper $sleeper;
    private RecordingListener $listener;

    protected function setUp(): void
    {
        $this->network = new ScriptedClient();
        $this->sleeper = new RecordingSleeper();
        $this->listener = new RecordingListener();
        $this->client = RetryingClient::create()
            ->withClock(clock: new SteppingClock(startingAt: 1000000, stepInNanoseconds: 5000000))
            ->withClient(client: $this->network)
            ->withBackoff(backoff: FixedDelay::ofMicroseconds(microseconds: 250000))
            ->withSleeper(sleeper: $this->sleeper)
            ->withListener(listener: $this->listener)
            ->withMaxAttempts(maxAttempts: 3)
            ->build();
    }

    public function testBuildWhenNoClientIsConfiguredThenClientNotConfigured(): void
    {
        /** @Then an exception indicating the missing client should be raised */
        $this->expectException(ClientNotConfigured::class);
        $this->expectExceptionMessage('A client must be provided to build the RetryingClient.');

        /** @When building without a PSR-18 client configured */
        RetryingClient::create()->build();
    }

    #[DataProvider('retryScenarios')]
    public function testSendRequestWhenStatusesGivenThenHonorsTheRetryPolicy(
        array $statuses,
        array $expectedSleeps,
        array $expectedOutcomes,
        int $expectedStatusCode
    ): void {
        /** @Given a network answering the scripted statuses */
        $this->network->answers(...array_map(
            static fn(int $status): ResponseInterface => new Psr17Factory()->createResponse($status),
            $statuses
        ));

        /** @When a request is sent */
        $response = $this->client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the response carries the expected status */
        self::assertSame($expectedStatusCode, $response->getStatusCode());

        /** @And the slept delays match the retry policy */
        self::assertSame($expectedSleeps, $this->sleeper->sleeps());

        /** @And the reported outcomes match the failed attempts */
        self::assertSame($expectedOutcomes, array_column($this->listener->failures(), 'outcome'));
    }

    public function testSendRequestWhenConnectionFailsOnceThenRetriesAndSucceeds(): void
    {
        /** @Given a connection failure on the first attempt */
        $failure = new PsrNetworkException('connection reset by peer');

        /** @And a network that fails once and then succeeds */
        $this->network->answers($failure, new Psr17Factory()->createResponse(200));

        /** @When a request is sent */
        $response = $this->client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the retry succeeds with the fixed delay slept once */
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([250000], $this->sleeper->sleeps());

        /** @And the failed attempt was reported as a connection reset */
        self::assertSame([AttemptOutcome::CONNECTION_RESET], array_column($this->listener->failures(), 'outcome'));
    }

    public function testSendRequestWhenConstructedDirectlyThenHonorsTheRetryPolicy(): void
    {
        /** @Given a client assembled through the public constructor */
        $client = new RetryingClient(
            clock: new SteppingClock(startingAt: 1000000, stepInNanoseconds: 5000000),
            client: $this->network,
            backoff: FixedDelay::ofMicroseconds(microseconds: 250000),
            sleeper: $this->sleeper,
            listener: $this->listener,
            maxAttempts: 2
        );

        /** @And a network answering a server error and then a success */
        $this->network->answers(new Psr17Factory()->createResponse(500), new Psr17Factory()->createResponse(200));

        /** @When a request is sent */
        $response = $client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the retry succeeds with the fixed delay slept once */
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([250000], $this->sleeper->sleeps());

        /** @And the failed attempt was reported as a server error */
        self::assertSame([AttemptOutcome::SERVER_ERROR], array_column($this->listener->failures(), 'outcome'));
    }

    public function testSendRequestWhenNoSeamsGivenThenSleepsForRealBetweenAttempts(): void
    {
        /** @Given a client with the system seams and a thirty millisecond fixed delay */
        $client = RetryingClient::create()
            ->withClient(client: $this->network)
            ->withBackoff(backoff: FixedDelay::ofMicroseconds(microseconds: 30000))
            ->withMaxAttempts(maxAttempts: 2)
            ->build();

        /** @And a network answering a server error and then a success */
        $this->network->answers(new Psr17Factory()->createResponse(503), new Psr17Factory()->createResponse(200));

        /** @And a captured monotonic instant */
        $startedAt = hrtime(true);

        /** @When a request is sent */
        $response = $client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the retry succeeded */
        self::assertSame(200, $response->getStatusCode());

        /** @And at least ten milliseconds of real suspension elapsed */
        self::assertGreaterThanOrEqual(10000, intdiv(hrtime(true) - $startedAt, 1000));
    }

    public function testSendRequestWhenNetworkKeepsFailingThenRethrowsTheLastFailure(): void
    {
        /** @Given a first connection failure */
        $first = new PsrNetworkException('connection reset by peer');

        /** @And a second connection failure */
        $second = new PsrNetworkException('connection timed out');

        /** @And a last connection failure */
        $last = new PsrNetworkException('connection timed out');

        /** @And a network that keeps failing */
        $this->network->answers($first, $second, $last);

        try {
            /** @When a request is sent */
            $this->client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));
            self::fail('The last connection failure was expected.');
        } catch (PsrNetworkException $failure) {
            /** @Then the rethrown failure is the last one */
            self::assertSame($last, $failure);
        }

        /** @And the fixed delay was slept between the attempts */
        self::assertSame([250000, 250000], $this->sleeper->sleeps());

        /** @And every failed attempt was reported with its attempt number */
        self::assertSame([1, 2, 3], array_column($this->listener->failures(), 'attemptNumber'));

        /** @And the reported outcomes follow the failure classification */
        self::assertSame(
            [AttemptOutcome::CONNECTION_RESET, AttemptOutcome::TIMEOUT, AttemptOutcome::TIMEOUT],
            array_column($this->listener->failures(), 'outcome')
        );
    }

    public function testSendRequestWhenExponentialBackoffGivenThenSleepsDoublingDelays(): void
    {
        /** @Given an exponential backoff with the jitter at its lower bound */
        $backoff = ExponentialBackoff::with(
            randomizer: new Randomizer(engine: new FixedBytesEngine(bytes: self::ZERO_BYTES))
        );

        /** @And a client retrying with that backoff */
        $client = RetryingClient::create()
            ->withClient(client: $this->network)
            ->withBackoff(backoff: $backoff)
            ->withSleeper(sleeper: $this->sleeper)
            ->withMaxAttempts(maxAttempts: 3)
            ->build();

        /** @And a network answering two server errors and then a success */
        $this->network->answers(
            new Psr17Factory()->createResponse(500),
            new Psr17Factory()->createResponse(500),
            new Psr17Factory()->createResponse(200)
        );

        /** @When a request is sent */
        $response = $client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the delays doubled between the attempts */
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([70000, 140000], $this->sleeper->sleeps());
    }

    public function testSendRequestWhenSingleRetryPolicyGivenThenSleepsHalfASecondOnce(): void
    {
        /** @Given a client allowing a single retry after half a second */
        $client = RetryingClient::create()
            ->withClient(client: $this->network)
            ->withBackoff(backoff: FixedDelay::ofMicroseconds(microseconds: 500000))
            ->withSleeper(sleeper: $this->sleeper)
            ->withMaxAttempts(maxAttempts: 2)
            ->build();

        /** @And a network answering a server error and then a success */
        $this->network->answers(new Psr17Factory()->createResponse(503), new Psr17Factory()->createResponse(200));

        /** @When a request is sent */
        $response = $client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the retry succeeded after a single sleep of half a second */
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([500000], $this->sleeper->sleeps());
    }

    public function testSendRequestWhenAttemptFailsThenListenerReceivesTheFailureDetails(): void
    {
        /** @Given a request bound to the network */
        $request = new Psr17Factory()->createRequest('GET', self::URL);

        /** @And a network answering a server error and then a success */
        $this->network->answers(new Psr17Factory()->createResponse(503), new Psr17Factory()->createResponse(200));

        /** @When the request is sent */
        $this->client->sendRequest($request);

        /** @Then the listener received the outcome, the request, the attempt number, and the elapsed interval */
        self::assertSame(
            [
                [
                    'outcome'               => AttemptOutcome::SERVER_ERROR,
                    'request'               => $request,
                    'attemptNumber'         => 1,
                    'elapsedInMilliseconds' => 5.0
                ]
            ],
            $this->listener->failures()
        );
    }

    public function testSendRequestWhenOnlyTheClientIsGivenThenDefaultsMakeThreeAttempts(): void
    {
        /** @Given a network answering three server errors */
        $this->network->answers(
            new Psr17Factory()->createResponse(500),
            new Psr17Factory()->createResponse(502),
            new Psr17Factory()->createResponse(503)
        );

        /** @And a client keeping the default backoff and attempt ceiling */
        $client = RetryingClient::create()
            ->withClient(client: $this->network)
            ->withSleeper(sleeper: $this->sleeper)
            ->build();

        /** @When a request is sent */
        $response = $client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));

        /** @Then the default ceiling exhausts after three attempts and the last response is returned */
        self::assertSame(503, $response->getStatusCode());
        self::assertCount(3, $this->network->requests());

        /** @And the two default exponential delays stay within the jitter bounds */
        self::assertCount(2, $this->sleeper->sleeps());
        self::assertGreaterThanOrEqual(70000, $this->sleeper->sleeps()[0]);
        self::assertLessThanOrEqual(130000, $this->sleeper->sleeps()[0]);
        self::assertGreaterThanOrEqual(140000, $this->sleeper->sleeps()[1]);
        self::assertLessThanOrEqual(260000, $this->sleeper->sleeps()[1]);
    }

    public function testSendRequestWhenFailureIsNotNetworkRelatedThenPropagatesWithoutRetrying(): void
    {
        /** @Given a network answering a client failure and then a success */
        $this->network->answers(new PsrClientException('generic failure'), new Psr17Factory()->createResponse(200));

        /** @Then the client failure propagates before any retry could succeed */
        $this->expectException(PsrClientException::class);

        /** @When a request is sent */
        $this->client->sendRequest(new Psr17Factory()->createRequest('GET', self::URL));
    }

    public static function retryScenarios(): array
    {
        return [
            'Success returns without retrying'                      => [
                'statuses'           => [200],
                'expectedSleeps'     => [],
                'expectedOutcomes'   => [],
                'expectedStatusCode' => 200
            ],
            'Client error returns without retrying'                 => [
                'statuses'           => [404],
                'expectedSleeps'     => [],
                'expectedOutcomes'   => [AttemptOutcome::CLIENT_ERROR],
                'expectedStatusCode' => 404
            ],
            'Server error retries until a success'                  => [
                'statuses'           => [500, 200],
                'expectedSleeps'     => [250000],
                'expectedOutcomes'   => [AttemptOutcome::SERVER_ERROR],
                'expectedStatusCode' => 200
            ],
            'Server errors return the last response at the ceiling' => [
                'statuses'           => [500, 502, 503],
                'expectedSleeps'     => [250000, 250000],
                'expectedOutcomes'   => [
                    AttemptOutcome::SERVER_ERROR,
                    AttemptOutcome::SERVER_ERROR,
                    AttemptOutcome::SERVER_ERROR
                ],
                'expectedStatusCode' => 503
            ]
        ];
    }
}
