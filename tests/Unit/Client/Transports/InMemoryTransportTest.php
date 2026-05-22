<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Transports;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transports\InMemoryTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\NoMoreResponses;

final class InMemoryTransportTest extends TestCase
{
    public function testSendWhenQueueExhaustedThenThrowsNoMoreResponses(): void
    {
        /** @Given a transport seeded with one response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::OK)]);

        /** @And a request to dispatch */
        $request = Request::get(url: '/dragons');

        /** @And the seeded response is already consumed */
        $transport->send(request: $request);

        /** @Then NoMoreResponses is thrown on the next call */
        $this->expectException(NoMoreResponses::class);

        /** @When sending a second request */
        $transport->send(request: $request);
    }

    public function testSendWhenMultipleResponsesQueuedThenServesInFifoOrder(): void
    {
        /** @Given a first queued response carrying OK */
        $first = Response::with(code: Code::OK);

        /** @And a second queued response carrying CREATED */
        $second = Response::with(code: Code::CREATED);

        /** @And a transport seeded with both responses */
        $transport = InMemoryTransport::with(responses: [$first, $second]);

        /** @And a request to dispatch */
        $request = Request::get(url: '/dragons');

        /** @When the queue is drained twice */
        $drained = [
            $transport->send(request: $request),
            $transport->send(request: $request)
        ];

        /** @Then the drained sequence preserves FIFO order */
        self::assertSame(Code::OK, $drained[0]->code());
        self::assertSame(Code::CREATED, $drained[1]->code());
    }

    public function testSendWhenQueueEmptyThenThrowsNoMoreResponsesImmediately(): void
    {
        /** @Given a transport seeded with zero responses */
        $transport = InMemoryTransport::with(responses: []);

        /** @And a request to dispatch */
        $request = Request::get(url: '/dragons');

        /** @Then NoMoreResponses is thrown immediately */
        $this->expectException(NoMoreResponses::class);

        /** @When sending a request against the empty queue */
        $transport->send(request: $request);
    }

    public function testSendWhenSingleResponseQueuedThenReturnsTheQueuedResponse(): void
    {
        /** @Given a transport seeded with a single CREATED response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::CREATED)]);

        /** @And a request to dispatch */
        $request = Request::get(url: '/dragons');

        /** @When the request is sent */
        $response = $transport->send(request: $request);

        /** @Then the returned response carries the queued CREATED code */
        self::assertSame(Code::CREATED, $response->code());
    }

    public function testSendWhenQueueEmptyThenExceptionMessageReferencesExhaustedIndex(): void
    {
        /** @Given a transport seeded with zero responses */
        $transport = InMemoryTransport::with(responses: []);

        /** @And a request to dispatch */
        $request = Request::get(url: '/dragons');

        /** @Then the raised exception message references the exhausted index */
        $this->expectException(NoMoreResponses::class);
        $this->expectExceptionMessage('InMemoryTransport has no response queued at index 0');

        /** @When sending a request against the empty queue */
        $transport->send(request: $request);
    }
}
