<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Drivers\Laminas;

use DateTimeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\Http\Drivers\Endpoint;
use Test\TinyBlocks\Http\Drivers\Middleware;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\ResponseCacheDirectives;
use TinyBlocks\Http\Server\Response;

final class LaminasTest extends TestCase
{
    private SapiEmitter $emitter;
    private Middleware $middleware;

    protected function setUp(): void
    {
        $this->emitter = new SapiEmitter();
        $this->middleware = new Middleware();
    }

    public function testEmitWhenLaminasEmitterUsedThenWritesBodyToOutputBuffer(): void
    {
        /** @Given a response with Content-Type, Cache-Control, and a custom header */
        $response = Response::ok(
            ['createdAt' => date(DateTimeInterface::ATOM)],
            ContentType::applicationJson(charset: Charset::UTF_8),
            CacheControl::fromResponseDirectives(ResponseCacheDirectives::noCache())
        )->withHeader('X-Request-ID', '123456');

        /** @When the response is emitted */
        ob_start();
        $this->emitter->emit($response);
        $actual = ob_get_clean();

        /** @Then the emitted body matches the response body */
        self::assertSame($response->getBody()->__toString(), $actual);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame('123456', $response->getHeaderLine('X-Request-ID'));
    }

    public function testProcessWhenLaminasMiddlewareInvokedThenReturnsConfiguredResponse(): void
    {
        /** @Given a valid request */
        $request = new ServerRequest(method: 'GET', uri: 'https://api.example.com/');

        /** @And an HTTP response is created with a 200 OK status, a JSON body, Content-Type, and Cache-Control */
        $response = Response::ok(
            ['createdAt' => date(DateTimeInterface::ATOM)],
            ContentType::applicationJson(charset: Charset::UTF_8),
            CacheControl::fromResponseDirectives(ResponseCacheDirectives::noCache())
        );

        /** @When the request is processed by the handler */
        $actual = $this->middleware->process($request, new Endpoint(response: $response));

        /** @Then the response is returned through the middleware unchanged */
        self::assertSame(Code::OK->value, $actual->getStatusCode());
        self::assertSame($response->getBody()->__toString(), $actual->getBody()->__toString());
        self::assertSame($response->getHeaders(), $actual->getHeaders());
    }
}
