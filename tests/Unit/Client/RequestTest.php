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
    public function testCreateWhenMinimalParametersGivenThenAccessorsReturnSuppliedValues(): void
    {
        /** @When creating a request with a URL and empty headers */
        $request = Request::create(
            url: 'https://api.example.com/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @Then accessors return the supplied values */
        self::assertSame('https://api.example.com/dragons', $request->url());
        self::assertSame(Method::GET, $request->method());
        self::assertNull($request->body());
        self::assertNull($request->query());
        self::assertSame([], $request->headers()->toArray());
    }

    public function testCreateWhenNullBodyGivenThenCarriesNoBody(): void
    {
        /** @When creating a request with an explicit null body */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @Then the body is null */
        self::assertNull($request->body());
    }

    public function testCreateWhenMultipleHeadersGivenThenMergesEntries(): void
    {
        /** @Given a Content-Type header with charset */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And another Content-Type header without charset */
        $accept = ContentType::applicationJson();

        /** @When creating a request with both headers merged */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::POST,
            headers: Headers::from($contentType, $accept)
        );

        /** @Then the merged headers contain Content-Type */
        self::assertTrue($request->headers()->has('Content-Type'));
    }

    public function testCreateWhenSameHeaderProvidedTwiceThenLastOneWins(): void
    {
        /** @Given a Content-Type header with charset */
        $first = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And another Content-Type header without charset */
        $second = ContentType::applicationJson();

        /** @When creating the request with both (last one wins) */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::POST,
            headers: Headers::from($first, $second)
        );

        /** @Then the last one wins for the Content-Type key */
        self::assertSame('application/json', $request->headers()->get('Content-Type'));
    }

    public function testCreateWhenQueryGivenThenPreservesArrayInProperty(): void
    {
        /** @Given query parameters */
        $query = ['sort' => 'name', 'order' => 'asc'];

        /** @When creating the request with query */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: $query,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @Then the query is preserved */
        self::assertSame($query, $request->query());
    }

    public function testWithUrlWhenInvokedThenReturnsNewInstanceWithReplacedUrl(): void
    {
        /** @Given a request with an original URL */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @When calling withUrl */
        $updated = $request->withUrl(url: '/dragons/42');

        /** @Then a new instance is returned with the URL replaced */
        self::assertNotSame($request, $updated);
        self::assertSame('/dragons/42', $updated->url());
        self::assertSame('/dragons', $request->url());
    }

    public function testWithQueryWhenInvokedThenReturnsNewInstanceWithReplacedQuery(): void
    {
        /** @Given a request with an original query */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: ['sort' => 'name'],
            method: Method::GET,
            headers: Headers::from()
        );

        /** @When calling withQuery */
        $updated = $request->withQuery(query: ['order' => 'asc']);

        /** @Then a new instance is returned with the query replaced */
        self::assertNotSame($request, $updated);
        self::assertSame(['order' => 'asc'], $updated->query());
        self::assertSame(['sort' => 'name'], $request->query());
    }

    public function testCreateWhenDistinctKeyHeadersGivenThenBothPresent(): void
    {
        /** @Given a Content-Type header */
        $contentType = ContentType::applicationJson();

        /** @And a Cache-Control header */
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::mustRevalidate());

        /** @When creating a request with both headers */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from($contentType, $cacheControl)
        );

        /** @Then both header keys are present in the merged result */
        self::assertCount(2, $request->headers()->toArray());
    }

    public function testWithMergedHeadersWhenCustomConflictsWithDefaultThenCustomWins(): void
    {
        /** @Given a request with a custom Content-Type header */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::POST,
            headers: Headers::from(ContentType::applicationJson(charset: Charset::UTF_8))
        );

        /** @And defaults that include the same header */
        $defaults = new Headers(entries: ['Content-Type' => 'application/json', 'Accept' => 'application/json']);

        /** @When merging defaults under the existing headers */
        $resolved = $request->withMergedHeaders(defaults: $defaults);

        /** @Then the custom header wins over the default */
        self::assertSame('application/json; charset=utf-8', $resolved->headers()->get('Content-Type'));
        self::assertSame('application/json', $resolved->headers()->get('Accept'));
    }

    public function testHeadersWhenMixedCaseGivenThenLookupIsCaseInsensitive(): void
    {
        /** @Given a request with a Content-Type header */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from(ContentType::applicationJson())
        );

        /** @When looking up the header with different casing */
        /** @Then the lookup succeeds regardless of case */
        self::assertTrue($request->headers()->has('content-type'));
        self::assertSame('application/json', $request->headers()->get('CONTENT-TYPE'));
    }

    public function testHeadersGetWhenMissingKeyGivenThenReturnsNull(): void
    {
        /** @Given a request with no headers */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @When looking up a non-existent header */
        /** @Then null is returned */
        self::assertNull($request->headers()->get('X-Missing'));
    }

    public function testHeadersWhenRequestCreatedThenExposesHeadersInstance(): void
    {
        /** @Given a request */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @When accessing headers */
        /** @Then a Headers instance is returned */
        self::assertInstanceOf(Headers::class, $request->headers());
    }
}
