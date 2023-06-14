<?php

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

class ResponseTest extends TestCase
{
    private const TEXT_PLAIN = 'Content-Type: text/plain';
    private const APPLICATION_JSON = 'Content-Type: application/json';

    public function testDefaultHeaders(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);
        $expected = ['header' => ['Content-Type' => self::APPLICATION_JSON]];

        self::assertEquals($expected, $response->getHeaders());
    }

    public function testGetProtocolVersion(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::assertEquals('1.1', $response->getProtocolVersion());
    }

    public function testGetHeaders(): void
    {
        $headers = (new HttpHeaders())->add(header: HttpContentType::APPLICATION_JSON);
        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);
        $expected = ['Content-Type' => self::APPLICATION_JSON];

        self::assertEquals($headers->toArray(), $response->getHeaders());
        self::assertEquals($expected, $response->getHeader(name: 'Content-Type'));
    }

    public function testHasHeader(): void
    {
        $headers = (new HttpHeaders())->add(header: HttpContentType::TEXT_PLAIN);
        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);
        $expected = ['Content-Type' => self::TEXT_PLAIN];

        self::assertTrue($response->hasHeader(name: 'Content-Type'));
        self::assertEquals($expected, $response->getHeader(name: 'Content-Type'));
    }

    public function testGetHeaderLine(): void
    {
        $headers = (new HttpHeaders())->add(header: HttpContentType::APPLICATION_JSON);
        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        self::assertEquals(self::APPLICATION_JSON, $response->getHeaderLine(name: 'Content-Type'));
    }

    public function testExceptionWhenBadMethodCallOnWithBody(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withBody> cannot be used.');

        $response->withBody(body: StreamFactory::from(data: []));
    }

    public function testExceptionWhenBadMethodCallOnWithStatus(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withStatus> cannot be used.');

        $response->withStatus(code: HttpCode::OK->value);
    }

    public function testExceptionWhenBadMethodCallOnWithHeader(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withHeader> cannot be used.');

        $response->withHeader(name: '', value: '');
    }

    public function testExceptionWhenBadMethodCallOnWithoutHeader(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withoutHeader> cannot be used.');

        $response->withoutHeader(name: '');
    }

    public function testExceptionWhenBadMethodCallOnWithAddedHeader(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withAddedHeader> cannot be used.');

        $response->withAddedHeader(name: '', value: '');
    }

    public function testExceptionWhenBadMethodCallOnWithProtocolVersion(): void
    {
        $response = Response::from(code: HttpCode::OK, data: [], headers: null);

        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withProtocolVersion> cannot be used.');

        $response->withProtocolVersion(version: '');
    }
}
