<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\ResponseCacheDirectives;

final class RequestTest extends TestCase
{
    public function testCreateWhenMinimalParametersGivenThenDefaultsToGet(): void
    {
        /** @When creating a request with only a URL */
        $request = Request::create(url: 'https://api.example.com/dragons');

        /** @Then defaults are applied */
        self::assertSame('https://api.example.com/dragons', $request->url);
        self::assertSame(Method::GET, $request->method);
        self::assertNull($request->body);
        self::assertNull($request->query);
        self::assertSame([], $request->headers->toArray());
    }

    public function testCreateWhenNullBodyGivenThenCarriesNoBody(): void
    {
        /** @When creating a request with an explicit null body */
        $request = Request::create(url: '/dragons');

        /** @Then the body is null */
        self::assertNull($request->body);
    }

    public function testCreateWhenMultipleHeadersGivenThenMergesEntries(): void
    {
        /** @Given two distinct headers */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);
        $accept = ContentType::applicationJson();

        /** @When creating a request with both headers via the variadic */
        $request = Request::create('/dragons', null, null, Method::POST, $contentType, $accept);

        /** @Then the merged headers contain Content-Type */
        self::assertTrue($request->headers->has('Content-Type'));
    }

    public function testCreateWhenSameHeaderProvidedTwiceThenLastOneWins(): void
    {
        /** @Given two Content-Type headers with different values */
        $first = ContentType::applicationJson(charset: Charset::UTF_8);
        $second = ContentType::applicationJson();

        /** @When creating the request with both (last one wins) */
        $request = Request::create('/dragons', null, null, Method::POST, $first, $second);

        /** @Then the last one wins for the Content-Type key */
        self::assertSame('application/json', $request->headers->get('Content-Type'));
    }

    public function testCreateWhenQueryGivenThenPreservesArrayInProperty(): void
    {
        /** @Given query parameters */
        $query = ['sort' => 'name', 'order' => 'asc'];

        /** @When creating the request with query */
        $request = Request::create(url: '/dragons', query: $query);

        /** @Then the query is preserved */
        self::assertSame($query, $request->query);
    }

    public function testWithUrlWhenInvokedThenReturnsNewInstanceWithReplacedUrl(): void
    {
        /** @Given a request with an original URL */
        $request = Request::create(url: '/dragons');

        /** @When calling withUrl */
        $updated = $request->withUrl(url: '/dragons/42');

        /** @Then a new instance is returned with the URL replaced */
        self::assertNotSame($request, $updated);
        self::assertSame('/dragons/42', $updated->url);
        self::assertSame('/dragons', $request->url);
    }

    public function testWithQueryWhenInvokedThenReturnsNewInstanceWithReplacedQuery(): void
    {
        /** @Given a request with an original query */
        $request = Request::create(url: '/dragons', query: ['sort' => 'name']);

        /** @When calling withQuery */
        $updated = $request->withQuery(query: ['order' => 'asc']);

        /** @Then a new instance is returned with the query replaced */
        self::assertNotSame($request, $updated);
        self::assertSame(['order' => 'asc'], $updated->query);
        self::assertSame(['sort' => 'name'], $request->query);
    }

    public function testCreateWhenDistinctKeyHeadersGivenThenBothPresent(): void
    {
        /** @Given two headers with distinct keys */
        $contentType = ContentType::applicationJson();
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::mustRevalidate());

        /** @When creating a request with both headers */
        $request = Request::create('/dragons', null, null, Method::GET, $contentType, $cacheControl);

        /** @Then both header keys are present in the merged result */
        self::assertCount(2, $request->headers->toArray());
    }

    public function testWithMergedHeadersWhenCustomConflictsWithDefaultThenCustomWins(): void
    {
        /** @Given a request with a custom Content-Type header */
        $request = Request::create(
            url: '/dragons',
            method: Method::POST,
            headers: ContentType::applicationJson(charset: Charset::UTF_8)
        );

        /** @When merging defaults that include the same header */
        $defaults = new Headers(entries: ['Content-Type' => 'application/json', 'Accept' => 'application/json']);
        $resolved = $request->withMergedHeaders(defaults: $defaults);

        /** @Then the custom header wins over the default */
        self::assertSame('application/json; charset=utf-8', $resolved->headers->get('Content-Type'));
        self::assertSame('application/json', $resolved->headers->get('Accept'));
    }

    public function testHeadersWhenMixedCaseGivenThenLookupIsCaseInsensitive(): void
    {
        /** @Given a request with a Content-Type header */
        $request = Request::create(
            url: '/dragons',
            headers: ContentType::applicationJson()
        );

        /** @When looking up the header with different casing */
        /** @Then the lookup succeeds regardless of case */
        self::assertTrue($request->headers->has('content-type'));
        self::assertSame('application/json', $request->headers->get('CONTENT-TYPE'));
    }

    public function testHeadersGetWhenMissingKeyGivenThenReturnsNull(): void
    {
        /** @Given a request with no headers */
        $request = Request::create(url: '/dragons');

        /** @When looking up a non-existent header */
        /** @Then null is returned */
        self::assertNull($request->headers->get('X-Missing'));
    }

    public function testHeadersWhenRequestCreatedThenExposesHeadersInstance(): void
    {
        /** @Given a request */
        $request = Request::create(url: '/dragons');

        /** @When accessing headers */
        /** @Then a Headers instance is returned */
        self::assertInstanceOf(Headers::class, $request->headers);
    }
}
