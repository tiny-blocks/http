<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

final class ResponseTest extends TestCase
{
    public function testDefaultHeaders(): void
    {
        /** @Given a Response with no headers provided */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @Then the default headers should include Status and Content-Type */
        self::assertEquals([
            'Status'       => [HttpCode::OK->message()],
            'Content-Type' => [HttpContentType::APPLICATION_JSON->value]
        ], $response->getHeaders());
    }

    public function testGetProtocolVersion(): void
    {
        /** @Given a Response */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @Then the protocol version should be 1.1 */
        self::assertEquals('1.1', $response->getProtocolVersion());
    }

    public function testGetHeaders(): void
    {
        /** @Given a Response with specific headers */
        $headers = HttpHeaders::build()->addFromContentType(header: HttpContentType::APPLICATION_JSON);
        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        /** @Then the Response should return the correct headers */
        self::assertEquals($headers->toArray(), $response->getHeaders());
        self::assertEquals([HttpContentType::APPLICATION_JSON->value], $response->getHeader(name: 'Content-Type'));
    }

    public function testHasHeader(): void
    {
        /** @Given a Response with a specific Content-Type header */
        $headers = HttpHeaders::build()->addFromContentType(header: HttpContentType::TEXT_PLAIN);
        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        /** @Then the Response should correctly indicate that it has the Content-Type header */
        self::assertTrue($response->hasHeader(name: 'Content-Type'));
        self::assertEquals([HttpContentType::TEXT_PLAIN->value], $response->getHeader(name: 'Content-Type'));
    }

    public function testGetHeaderLine(): void
    {
        /** @Given a Response with a specific Content-Type header */
        $headers = HttpHeaders::build()->addFromContentType(header: HttpContentType::APPLICATION_JSON);
        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        /** @Then the header line should match the expected value */
        self::assertEquals(HttpContentType::APPLICATION_JSON->value, $response->getHeaderLine(name: 'Content-Type'));
    }

    public function testWithHeader(): void
    {
        /** @Given a Response */
        $value = '2850bf62-8383-4e9f-b237-d41247a1df3b';
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @When adding a new header */
        $response->withHeader(name: 'Token', value: $value);

        /** @Then the new header should be included in the Response */
        self::assertEquals([$value], $response->getHeader(name: 'Token'));
    }

    public function testWithoutHeader(): void
    {
        /** @Given a Response with default headers */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @When removing the Status header */
        $response->withoutHeader(name: 'Status');

        /** @Then the Status header should be empty and Content-Type should remain intact */
        self::assertEmpty($response->getHeader(name: 'Status'));
        self::assertEquals([HttpContentType::APPLICATION_JSON->value], $response->getHeader(name: 'Content-Type'));
    }

    public function testExceptionWhenBadMethodCallOnWithBody(): void
    {
        /** @Given a Response */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @Then a BadMethodCall exception should be thrown when calling withBody */
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withBody> cannot be used.');

        /** @When attempting to call withBody */
        $response->withBody(body: StreamFactory::from(data: []));
    }

    public function testExceptionWhenBadMethodCallOnWithStatus(): void
    {
        /** @Given a Response */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @Then a BadMethodCall exception should be thrown when calling withStatus */
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withStatus> cannot be used.');

        /** @When attempting to call withStatus */
        $response->withStatus(code: HttpCode::OK->value);
    }

    public function testExceptionWhenBadMethodCallOnWithAddedHeader(): void
    {
        /** @Given a Response */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @Then a BadMethodCall exception should be thrown when calling withAddedHeader */
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withAddedHeader> cannot be used.');

        /** @When attempting to call withAddedHeader */
        $response->withAddedHeader(name: '', value: '');
    }

    public function testExceptionWhenBadMethodCallOnWithProtocolVersion(): void
    {
        /** @Given a Response */
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        /** @Then a BadMethodCall exception should be thrown when calling withProtocolVersion */
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withProtocolVersion> cannot be used.');

        /** @When attempting to call withProtocolVersion */
        $response->withProtocolVersion(version: '');
    }
}
