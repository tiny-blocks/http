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
    public function testResponsesAreReturnedInFifoOrder(): void
    {
        /** @Given a transport seeded with two responses */
        $first = Response::with(code: Code::OK);
        $second = Response::with(code: Code::CREATED);
        $transport = InMemoryTransport::with(responses: [$first, $second]);

        /** @When calling send twice */
        $request = Request::create(url: '/dragons');
        $responseOne = $transport->send(request: $request);
        $responseTwo = $transport->send(request: $request);

        /** @Then responses are returned in FIFO order */
        self::assertSame(Code::OK, $responseOne->code());
        self::assertSame(Code::CREATED, $responseTwo->code());
    }

    public function testExhaustedTransportThrowsNoMoreResponses(): void
    {
        /** @Given a transport seeded with one response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::OK)]);
        $request = Request::create(url: '/dragons');
        $transport->send(request: $request);

        /** @Then NoMoreResponses is thrown on the second call */
        $this->expectException(NoMoreResponses::class);

        /** @When sending a second request */
        $transport->send(request: $request);
    }

    public function testEmptyTransportThrowsNoMoreResponsesImmediately(): void
    {
        /** @Given a transport seeded with zero responses */
        $transport = InMemoryTransport::with(responses: []);

        /** @Then NoMoreResponses is thrown immediately */
        $this->expectException(NoMoreResponses::class);

        /** @When calling send */
        $transport->send(request: Request::create(url: '/dragons'));
    }

    public function testSingleResponseTransportReturnsCorrectResponse(): void
    {
        /** @Given a transport seeded with one response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::CREATED)]);

        /** @When sending one request */
        $response = $transport->send(request: Request::create(url: '/dragons'));

        /** @Then the response is correct */
        self::assertSame(Code::CREATED, $response->code());
    }
}
