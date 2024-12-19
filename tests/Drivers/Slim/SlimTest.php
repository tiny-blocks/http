<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Drivers\Slim;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;
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
    private Middleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new Middleware();
    }

    public function testSuccessfulResponse(): void
    {
        /** @Given valid data */
        $payload = ['id' => PHP_INT_MAX, 'name' => 'Drakkor Emberclaw'];

        /** @And this data is used to create a request */
        $request = RequestFactory::postFrom(payload: $payload);

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
}
