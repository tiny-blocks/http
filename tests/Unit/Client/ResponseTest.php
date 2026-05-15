<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\SynthesizedResponseHasNoRaw;

final class ResponseTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testResponseWith200AndJsonBodyAllowsTypedAccess(): void
    {
        /** @Given a 200 response with a JSON body */
        $psrResponse = $this->factory->createResponse(200)
            ->withBody($this->factory->createStream('{"id":42,"name":"Hydra"}'));

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then typed body access works correctly */
        self::assertSame(42, $response->body()->get(key: 'id')->toInteger());
        self::assertSame('Hydra', $response->body()->get(key: 'name')->toString());
        self::assertSame(Code::OK, $response->code());
    }

    public function testResponseWith204AndNoBodyReturnsEmptyArray(): void
    {
        /** @Given a 204 response with no body */
        $psrResponse = $this->factory->createResponse(204);

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then the body array is empty */
        self::assertSame([], $response->body()->toArray());
        self::assertSame(Code::NO_CONTENT, $response->code());
    }

    public function testResponseWithNonJsonBodyReturnsSafeEmptyArray(): void
    {
        /** @Given a 200 response with a non-JSON body */
        $psrResponse = $this->factory->createResponse(200)
            ->withBody($this->factory->createStream('plain text'));

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then the body gracefully returns an empty array */
        self::assertSame([], $response->body()->toArray());
    }

    public function testResponseWith200IsSuccessAndNotError(): void
    {
        /** @Given a 200 response */
        $psrResponse = $this->factory->createResponse(200);

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then isSuccess is true and isError is false */
        self::assertTrue($response->isSuccess());
        self::assertFalse($response->isError());
    }

    public function testResponseWith500IsErrorAndNotSuccess(): void
    {
        /** @Given a 500 response */
        $psrResponse = $this->factory->createResponse(500);

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then isError is true and isSuccess is false */
        self::assertTrue($response->isError());
        self::assertFalse($response->isSuccess());
    }

    public function testResponseHeadersAreFlattenedToStrings(): void
    {
        /** @Given a response with two distinct headers */
        $psrResponse = $this->factory->createResponse(200)
            ->withHeader('X-Trace', 'abc')
            ->withHeader('X-Request-ID', '123');

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then headers() returns all headers accessible via the Headers value object */
        self::assertSame('abc', $response->headers()->get('X-Trace'));
        self::assertSame('123', $response->headers()->get('X-Request-ID'));
    }

    public function testRawReturnsUnderlyingPsrResponse(): void
    {
        /** @Given a PSR response */
        $psrResponse = $this->factory->createResponse(200);

        /** @When wrapping and then unwrapping */
        $response = Response::from(response: $psrResponse);

        /** @Then raw() returns the exact original instance */
        self::assertSame($psrResponse, $response->raw());
    }

    public function testSynthesizedResponseWithCodeAndBodyWorks(): void
    {
        /** @Given code and body data */
        /** @When synthesizing a response via with() */
        $response = Response::with(code: Code::CREATED, body: ['id' => 1]);

        /** @Then code and body are accessible */
        self::assertSame(Code::CREATED, $response->code());
        self::assertSame(1, $response->body()->get(key: 'id')->toInteger());
        self::assertTrue($response->isSuccess());
        self::assertFalse($response->isError());
    }

    public function testSynthesizedResponseRawThrowsSynthesizedResponseHasNoRaw(): void
    {
        /** @Given a synthesized response */
        $response = Response::with(code: Code::OK);

        /** @Then SynthesizedResponseHasNoRaw is thrown */
        $this->expectException(SynthesizedResponseHasNoRaw::class);

        /** @When calling raw() */
        $response->raw();
    }

    public function testSynthesizedResponseWithNullBodyReturnsEmptyArray(): void
    {
        /** @Given a synthesized response with null body */
        /** @When creating the response */
        $response = Response::with(code: Code::NO_CONTENT);

        /** @Then body is empty */
        self::assertSame([], $response->body()->toArray());
    }

    public function testResponseWithSeekableStreamCanBeConsumedAgainViaRaw(): void
    {
        /** @Given a 200 response with a JSON body in a seekable stream */
        $psrResponse = $this->factory->createResponse(200)
            ->withBody($this->factory->createStream('{"name":"Hydra"}'));

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then the body was parsed correctly */
        self::assertSame('Hydra', $response->body()->get(key: 'name')->toString());

        /** @And the underlying stream is still readable via raw() */
        $raw = $response->raw()->getBody();
        $raw->rewind();
        self::assertSame('{"name":"Hydra"}', $raw->getContents());
    }

    public function testResponseFromAdvancedSeekableStreamParsesBodyFromStart(): void
    {
        /** @Given a seekable stream advanced past its start */
        $stream = $this->factory->createStream('{"name":"Hydra"}');
        $stream->getContents();

        /** @And a 200 response using that stream */
        $psrResponse = $this->factory->createResponse(200)->withBody($stream);

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then the body is parsed correctly despite the advanced stream position */
        self::assertSame('Hydra', $response->body()->get(key: 'name')->toString());

        /** @And the stream is at position zero after parsing so it can be re-read without a manual rewind */
        self::assertSame('{"name":"Hydra"}', $response->raw()->getBody()->getContents());
    }

    public function testResponseWithDeeplyNestedJsonBeyondDepthDegradesToEmpty(): void
    {
        /** @Given a JSON string nested deeper than 64 levels */
        $json = str_repeat('{"a":', 65) . '1' . str_repeat('}', 65);
        $psrResponse = $this->factory->createResponse(200)
            ->withBody($this->factory->createStream($json));

        /** @When wrapping the PSR response */
        $response = Response::from(response: $psrResponse);

        /** @Then body degrades gracefully to an empty array */
        self::assertSame([], $response->body()->toArray());
    }
}
