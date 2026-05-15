<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Internal\Client;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Exceptions\MalformedPath;
use TinyBlocks\Http\Internal\Client\RequestResolver;
use TinyBlocks\Http\Method;

final class RequestResolverTest extends TestCase
{
    public function testResolveWhenNoExplicitHeadersThenAppliesJsonDefaults(): void
    {
        /** @Given a resolver with a base URL and a request with no headers */
        $resolver = RequestResolver::withBaseUrl(baseUrl: 'https://api.example.com');
        $request = Request::create(url: '/dragons');

        /** @When resolving the request */
        $resolved = $resolver->resolve(request: $request);

        /** @Then the resolved request carries Content-Type and Accept defaults */
        self::assertSame('application/json', $resolved->headers->get('Content-Type'));
        self::assertSame('application/json', $resolved->headers->get('Accept'));
    }

    public function testResolveWhenExplicitContentTypeGivenThenWinsOverDefault(): void
    {
        /** @Given a resolver and a request with an explicit Content-Type */
        $resolver = RequestResolver::withBaseUrl(baseUrl: 'https://api.example.com');
        $request = Request::create(
            url: '/dragons',
            method: Method::POST,
            headers: ContentType::applicationJson(charset: Charset::UTF_8)
        );

        /** @When resolving the request */
        $resolved = $resolver->resolve(request: $request);

        /** @Then the explicit Content-Type wins over the default */
        self::assertSame('application/json; charset=utf-8', $resolved->headers->get('Content-Type'));
    }

    public function testResolveWhenRelativeUrlGivenThenComposesAgainstBaseUrl(): void
    {
        /** @Given a resolver with a base URL and a request with a relative path */
        $resolver = RequestResolver::withBaseUrl(baseUrl: 'https://api.example.com');
        $request = Request::create(url: '/dragons');

        /** @When resolving the request */
        $resolved = $resolver->resolve(request: $request);

        /** @Then the resolved URL is absolute */
        self::assertSame('https://api.example.com/dragons', $resolved->url);
    }

    public function testResolveWhenQueryGivenThenEmbedsInUrlAndClearsRequestQuery(): void
    {
        /** @Given a resolver and a request with query parameters */
        $resolver = RequestResolver::withBaseUrl(baseUrl: 'https://api.example.com');
        $request = Request::create(url: '/dragons', query: ['sort' => 'name', 'order' => 'asc']);

        /** @When resolving the request */
        $resolved = $resolver->resolve(request: $request);

        /** @Then the query is embedded in the URL and cleared from the request object */
        self::assertStringContainsString('sort=name', $resolved->url);
        self::assertStringContainsString('order=asc', $resolved->url);
        self::assertNull($resolved->query);
    }

    public function testResolveWhenMalformedPathGivenThenThrowsMalformedPath(): void
    {
        /** @Given a resolver and a request with a malformed path */
        $resolver = RequestResolver::withBaseUrl(baseUrl: 'https://api.example.com');
        $request = Request::create(url: '//evil.example.com/attack');

        /** @Then MalformedPath is thrown */
        $this->expectException(MalformedPath::class);

        /** @When resolving the request */
        $resolver->resolve(request: $request);
    }
}
