<?php

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = Response::from(code: HttpCode::OK, data: [], headers: []);
    }

    public function testGetProtocolVersion(): void
    {
        self::assertEquals('1.1', $this->response->getProtocolVersion());
    }

    public function testGetHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Auth-Token' => 'abc123'
        ];

        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        self::assertEquals($headers, $response->getHeaders());
    }

    public function testHasHeader(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Auth-Token' => 'abc123'
        ];

        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        self::assertTrue($response->hasHeader(name: 'Content-Type'));
        self::assertFalse($response->hasHeader(name: 'Authorization'));
    }

    public function testGetHeader(): void
    {
        $headers = [
            'Content-Type'    => ['application/json'],
            'X-Auth-Token'    => ['abc123'],
            'X-Custom-Header' => ['value1', 'value2']
        ];

        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        self::assertEquals(['application/json'], $response->getHeader(name: 'Content-Type'));
        self::assertEquals(['abc123'], $response->getHeader(name: 'X-Auth-Token'));
        self::assertEquals(['value1', 'value2'], $response->getHeader(name: 'X-Custom-Header'));
        self::assertEquals([], $response->getHeader(name: 'Authorization'));
    }

    public function testGetHeaderLine(): void
    {
        $headers = [
            'Content-Type'    => ['application/json'],
            'X-Auth-Token'    => ['abc123'],
            'X-Custom-Header' => ['value1', 'value2']
        ];

        $response = Response::from(code: HttpCode::OK, data: [], headers: $headers);

        self::assertEquals('application/json', $response->getHeaderLine(name: 'Content-Type'));
        self::assertEquals('abc123', $response->getHeaderLine(name: 'X-Auth-Token'));
        self::assertEquals('value1, value2', $response->getHeaderLine(name: 'X-Custom-Header'));
        self::assertEquals('', $response->getHeaderLine(name: 'Authorization'));
    }

    public function testExceptionWhenBadMethodCallOnWithBody(): void
    {
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withBody> cannot be used.');

        $this->response->withBody(body: StreamFactory::from(data: []));
    }

    public function testExceptionWhenBadMethodCallOnWithStatus(): void
    {
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withStatus> cannot be used.');

        $this->response->withStatus(code: HttpCode::OK->value);
    }

    public function testExceptionWhenBadMethodCallOnWithHeader(): void
    {
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withHeader> cannot be used.');

        $this->response->withHeader(name: '', value: '');
    }

    public function testExceptionWhenBadMethodCallOnWithoutHeader(): void
    {
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withoutHeader> cannot be used.');

        $this->response->withoutHeader(name: '');
    }

    public function testExceptionWhenBadMethodCallOnWithAddedHeader(): void
    {
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withAddedHeader> cannot be used.');

        $this->response->withAddedHeader(name: '', value: '');
    }

    public function testExceptionWhenBadMethodCallOnWithProtocolVersion(): void
    {
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <TinyBlocks\Http\Internal\Response::withProtocolVersion> cannot be used.');

        $this->response->withProtocolVersion(version: '');
    }
}
