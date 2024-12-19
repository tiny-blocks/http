<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Drivers\Slim;

use DateTimeInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\ResponseEmitter;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Drivers\Endpoint;
use TinyBlocks\Http\Drivers\Middleware;
use TinyBlocks\Http\Response;
use TinyBlocks\Http\ResponseCacheDirectives;

final class SlimTest extends TestCase
{
    private ResponseEmitter $emitter;

    private Middleware $middleware;

    protected function setUp(): void
    {
        $this->emitter = new ResponseEmitter();
        $this->middleware = new Middleware();
    }

    /**
     * @throws Exception
     */
    public function testSuccessfulRequestProcessingWithSlim(): void
    {
        /** @Given a valid request */
        $request = $this->createMock(ServerRequestInterface::class);

        /** @And the Content-Type for the response is set to application/json with UTF-8 charset */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And a Cache-Control header is set with no-cache directive */
        $cacheControl = CacheControl::fromResponseDirectives(noCache: ResponseCacheDirectives::noCache());

        /** @And an HTTP response is created with a 200 OK status and a body containing the creation timestamp */
        $response = Response::ok(['createdAt' => date(DateTimeInterface::ATOM)], $contentType, $cacheControl);

        /** @When the request is processed by the handler */
        $actual = $this->middleware->process(request: $request, handler: new Endpoint(response: $response));

        /** @Then the response status should indicate success */
        self::assertSame(Code::OK->value, $actual->getStatusCode());

        /** @And the response body should match the expected body */
        self::assertSame($response->getBody()->getContents(), $actual->getBody()->getContents());

        /** @And the response headers should match the expected headers */
        self::assertSame($response->getHeaders(), $actual->getHeaders());
    }

    public function testResponseEmissionWithSlim(): void
    {
        /** @Given the Content-Type for the response is set to application/json with UTF-8 charset */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And a Cache-Control header is set with no-cache directive */
        $cacheControl = CacheControl::fromResponseDirectives(noCache: ResponseCacheDirectives::noCache());

        /** @And an HTTP response is created with a 200 OK status and a body containing the creation timestamp */
        $response = Response::ok(
            ['createdAt' => date(DateTimeInterface::ATOM)],
            $contentType,
            $cacheControl
        )->withHeader(name: 'X-Request-ID', value: '123456');

        /** @When the response is emitted */
        ob_start();
        $this->emitter->emit($response);
        $actual = ob_get_clean();

        /** @Then the emitted response content should match the response body */
        self::assertSame($response->getBody()->__toString(), $actual);

        /** @And the response status code should be 200 */
        self::assertSame(200, $response->getStatusCode());

        /** @And the reason phrase should be 'OK' */
        self::assertSame('OK', $response->getReasonPhrase());

        /** @And the response should contain the X-Request-ID header */
        self::assertSame('123456', $response->getHeaderLine(name: 'X-Request-ID'));
    }
}
