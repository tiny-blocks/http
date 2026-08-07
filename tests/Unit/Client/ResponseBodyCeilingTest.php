<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\Http\Unit\ChunkedStream;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Exceptions\ResponseBodyTooLarge;

final class ResponseBodyCeilingTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testFromWhenBodyAtCeilingGivenThenDecodesPayload(): void
    {
        /** @Given a JSON payload */
        $payload = '{"id":1}';

        /** @And a response carrying that payload */
        $psrResponse = $this->factory->createResponse(200)->withBody($this->factory->createStream($payload));

        /** @When wrapping the response with a ceiling equal to the payload size */
        $response = Response::from(response: $psrResponse, maxBytes: strlen($payload));

        /** @Then the payload is decoded */
        self::assertSame(['id' => 1], $response->body()->toArray());
    }

    public function testFromWhenChunkedStreamGivenThenAssemblesFullPayload(): void
    {
        /** @Given a stream that yields the payload three bytes at a time */
        $payload = '{"id":1,"name":"dragon"}';
        $stream = new ChunkedStream(payload: $payload, chunkSize: 3);

        /** @And a response carrying that stream */
        $psrResponse = $this->factory->createResponse(200)->withBody($stream);

        /** @When wrapping the response with a ceiling above the payload size */
        $response = Response::from(response: $psrResponse, maxBytes: 1024);

        /** @Then every chunk is assembled before decoding */
        self::assertSame(['id' => 1, 'name' => 'dragon'], $response->body()->toArray());
    }

    public function testFromWhenChunkedStreamGivenThenNeverRequestsBeyondCeiling(): void
    {
        /** @Given a stream that yields a twenty byte payload seven bytes at a time */
        $payload = '{"name":"dragonfly"}';
        $stream = new ChunkedStream(payload: $payload, chunkSize: 7);

        /** @And a response carrying that stream */
        $psrResponse = $this->factory->createResponse(200)->withBody($stream);

        /** @When wrapping the response with a ceiling of one hundred bytes */
        $response = Response::from(response: $psrResponse, maxBytes: 100);

        /** @Then the payload is decoded */
        self::assertSame(['name' => 'dragonfly'], $response->body()->toArray());

        /** @And every read requests the remaining allowance, never more */
        self::assertSame([101, 94, 87, 81], $stream->requestedLengths());
    }

    public function testFromWhenBodyExceedsCeilingGivenThenThrowsResponseBodyTooLarge(): void
    {
        /** @Given a JSON payload */
        $payload = '{"id":1}';

        /** @And a response carrying that payload */
        $psrResponse = $this->factory->createResponse(200)->withBody($this->factory->createStream($payload));

        /** @Then an exception describing the crossed ceiling is thrown */
        $this->expectException(ResponseBodyTooLarge::class);
        $this->expectExceptionMessage('Response body exceeds the maximum of 7 bytes.');

        /** @When wrapping the response with a ceiling below the payload size */
        Response::from(response: $psrResponse, maxBytes: (strlen($payload) - 1));
    }
}
