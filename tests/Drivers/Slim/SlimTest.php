<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Drivers\Slim;

use DateTimeInterface;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Slim\ResponseEmitter;
use Test\TinyBlocks\Http\Drivers\Endpoint;
use Test\TinyBlocks\Http\Drivers\Middleware;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\ResponseCacheDirectives;
use TinyBlocks\Http\Server\Response;

final class SlimTest extends TestCase
{
    private ResponseEmitter $emitter;
    private Middleware $middleware;

    protected function setUp(): void
    {
        $this->emitter = new ResponseEmitter();
        $this->middleware = new Middleware();
    }

    public function testProcessWhenSlimMiddlewareInvokedThenReturnsConfiguredResponse(): void
    {
        /** @Given a valid request */
        $request = new ServerRequest(method: 'GET', uri: 'https://api.example.com/');

        /** @And the Content-Type and Cache-Control headers are set */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::noCache());

        /** @And an HTTP response is created with a 200 OK status and a JSON body */
        $response = Response::ok(['createdAt' => date(DateTimeInterface::ATOM)], $contentType, $cacheControl);

        /** @When the request is processed by the handler */
        $actual = $this->middleware->process(request: $request, handler: new Endpoint(response: $response));

        /** @Then the response is returned through the middleware unchanged */
        self::assertSame(Code::OK->value, $actual->getStatusCode());
        self::assertSame($response->getBody()->__toString(), $actual->getBody()->__toString());
        self::assertSame($response->getHeaders(), $actual->getHeaders());
    }

    public function testEmitWhenSlimEmitterUsedThenWritesBodyToOutputBuffer(): void
    {
        /** @Given a response with Content-Type, Cache-Control, and a custom header */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::noCache());
        $response = Response::ok(
            ['createdAt' => date(DateTimeInterface::ATOM)],
            $contentType,
            $cacheControl
        )->withHeader(name: 'X-Request-ID', value: '123456');

        /** @When the response is emitted */
        ob_start();
        $this->emitter->emit($response);
        $actual = ob_get_clean();

        /** @Then the emitted body matches the response body */
        self::assertSame($response->getBody()->__toString(), $actual);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame('123456', $response->getHeaderLine(name: 'X-Request-ID'));
    }
}
