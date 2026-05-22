<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function testForWhenMethodAndUrlGivenThenAccessorsReturnSuppliedValues(): void
    {
        /** @When creating a request for a specific method and URL */
        $request = Request::for(method: Method::GET, url: 'https://api.example.com/dragons');

        /** @Then accessors return the supplied values */
        self::assertSame('https://api.example.com/dragons', $request->url());
        self::assertSame(Method::GET, $request->method());
        self::assertNull($request->body());
        self::assertNull($request->queryParameters());
        self::assertSame([], $request->headers()->toArray());
    }

    public function testForWhenNullBodyGivenThenCarriesNoBody(): void
    {
        /** @When creating a request with an explicit null body */
        $request = Request::for(method: Method::GET, url: '/dragons');

        /** @Then the body is null */
        self::assertNull($request->body());
    }

    public function testForWhenMultipleHeadersGivenThenMergesEntries(): void
    {
        /** @Given a Content-Type header with charset */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And another Content-Type header without charset */
        $accept = ContentType::applicationJson();

        /** @When creating a request with both headers merged */
        $request = Request::for(
            method: Method::POST,
            url: '/dragons',
            headers: Headers::from($contentType, $accept)
        );

        /** @Then the merged headers contain Content-Type */
        self::assertTrue($request->headers()->has('Content-Type'));
    }

    public function testForWhenSameHeaderProvidedTwiceThenLastOneWins(): void
    {
        /** @Given a Content-Type header with charset */
        $first = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And another Content-Type header without charset */
        $second = ContentType::applicationJson();

        /** @When creating the request with both (last one wins) */
        $request = Request::for(
            method: Method::POST,
            url: '/dragons',
            headers: Headers::from($first, $second)
        );

        /** @Then the last one wins for the Content-Type key */
        self::assertSame('application/json', $request->headers()->get('Content-Type'));
    }

    public function testForWhenQueryParametersGivenThenPreservesArrayInProperty(): void
    {
        /** @Given query parameters */
        $queryParameters = ['sort' => 'name', 'order' => 'asc'];

        /** @When creating the request with query parameters */
        $request = Request::for(method: Method::GET, url: '/dragons', queryParameters: $queryParameters);

        /** @Then the query parameters are preserved */
        self::assertSame($queryParameters, $request->queryParameters());
    }

    public function testWithUrlWhenInvokedThenReturnsNewInstanceWithReplacedUrl(): void
    {
        /** @Given a request with an original URL */
        $request = Request::get(url: '/dragons');

        /** @When calling withUrl */
        $updated = $request->withUrl(url: '/dragons/42');

        /** @Then a new instance is returned with the URL replaced */
        self::assertNotSame($request, $updated);
        self::assertSame('/dragons/42', $updated->url());
        self::assertSame('/dragons', $request->url());
    }

    public function testWithQueryParametersWhenInvokedThenReturnsNewInstanceWithReplacedQueryParameters(): void
    {
        /** @Given a request with original query parameters */
        $request = Request::get(url: '/dragons', queryParameters: ['sort' => 'name']);

        /** @When calling withQueryParameters */
        $updated = $request->withQueryParameters(queryParameters: ['order' => 'asc']);

        /** @Then a new instance is returned with the query parameters replaced */
        self::assertNotSame($request, $updated);
        self::assertSame(['order' => 'asc'], $updated->queryParameters());
        self::assertSame(['sort' => 'name'], $request->queryParameters());
    }

    public function testWithHeaderWhenNewNameGivenThenAppendsHeader(): void
    {
        /** @Given a request with no custom headers */
        $request = Request::get(url: '/dragons');

        /** @When adding a new header */
        $updated = $request->withHeader(name: 'X-Trace-Id', value: 'abc-123');

        /** @Then the new header is present on the updated instance */
        self::assertSame('abc-123', $updated->headers()->get('X-Trace-Id'));

        /** @And the original instance is unchanged */
        self::assertNull($request->headers()->get('X-Trace-Id'));
    }

    public function testWithHeaderWhenExistingNameGivenThenReplacesHeader(): void
    {
        /** @Given a request with a Content-Type header */
        $request = Request::post(
            url: '/dragons',
            headers: Headers::from(ContentType::applicationJson())
        );

        /** @When replacing the Content-Type header */
        $updated = $request->withHeader(name: 'Content-Type', value: 'text/plain');

        /** @Then the new value replaces the original */
        self::assertSame('text/plain', $updated->headers()->get('Content-Type'));

        /** @And the original instance retains its original value */
        self::assertSame('application/json', $request->headers()->get('Content-Type'));
    }

    public function testWithHeaderWhenCasingDiffersThenReplacesExistingEntry(): void
    {
        /** @Given a request with a Content-Type header stored under mixed case */
        $request = Request::post(
            url: '/dragons',
            headers: Headers::from(ContentType::applicationJson())
        );

        /** @When replacing using a different casing */
        $updated = $request->withHeader(name: 'content-type', value: 'text/plain');

        /** @Then only one Content-Type entry exists and it carries the new value */
        self::assertSame('text/plain', $updated->headers()->get('Content-Type'));
        self::assertCount(1, $updated->headers()->toArray());
    }

    public function testForWhenDistinctKeyHeadersGivenThenBothPresent(): void
    {
        /** @Given a Content-Type header */
        $contentType = ContentType::applicationJson();

        /** @And a Cache-Control header */
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::mustRevalidate());

        /** @When creating a request with both headers */
        $request = Request::for(
            method: Method::GET,
            url: '/dragons',
            headers: Headers::from($contentType, $cacheControl)
        );

        /** @Then both header keys are present in the merged result */
        self::assertCount(2, $request->headers()->toArray());
    }

    public function testWithMergedHeadersWhenCustomConflictsWithDefaultThenCustomWins(): void
    {
        /** @Given a request with a custom Content-Type header */
        $request = Request::post(
            url: '/dragons',
            headers: Headers::from(ContentType::applicationJson(charset: Charset::UTF_8))
        );

        /** @And defaults that include the same header */
        $defaults = Headers::fromArray(entries: ['Content-Type' => 'application/json', 'Accept' => 'application/json']);

        /** @When merging defaults under the existing headers */
        $resolved = $request->withMergedHeaders(defaults: $defaults);

        /** @Then the custom header wins over the default */
        self::assertSame('application/json; charset=utf-8', $resolved->headers()->get('Content-Type'));
        self::assertSame('application/json', $resolved->headers()->get('Accept'));
    }

    public function testHeadersWhenMixedCaseGivenThenLookupIsCaseInsensitive(): void
    {
        /** @Given a request with a Content-Type header */
        $request = Request::get(url: '/dragons', headers: Headers::from(ContentType::applicationJson()));

        /** @When looking up the header with different casing */
        /** @Then the lookup succeeds regardless of case */
        self::assertTrue($request->headers()->has('content-type'));
        self::assertSame('application/json', $request->headers()->get('CONTENT-TYPE'));
    }

    public function testHeadersGetWhenMissingKeyGivenThenReturnsNull(): void
    {
        /** @Given a request with no headers */
        $request = Request::get(url: '/dragons');

        /** @When looking up a non-existent header */
        /** @Then null is returned */
        self::assertNull($request->headers()->get('X-Missing'));
    }

    public function testHeadersWhenRequestCreatedThenExposesHeadersInstance(): void
    {
        /** @Given a request */
        $request = Request::get(url: '/dragons');

        /** @When accessing headers */
        /** @Then a Headers instance is returned */
        self::assertInstanceOf(Headers::class, $request->headers());
    }

    public function testForWhenNonStandardMethodGivenThenMethodIsPreserved(): void
    {
        /** @When creating a request for a non-standard method */
        $request = Request::for(method: Method::OPTIONS, url: '/dragons');

        /** @Then the method is preserved */
        self::assertSame(Method::OPTIONS, $request->method());
    }

    #[DataProvider('shortcutMethodCases')]
    public function testShortcutWhenInvokedThenMethodMatchesExpected(Method $expected, Closure $factory): void
    {
        /** @Given a shortcut factory for a specific HTTP method */

        /** @When the factory is called */
        $request = $factory();

        /** @Then the method matches the expected enum case */
        self::assertSame($expected, $request->method());
    }

    #[DataProvider('noBodyShortcutCases')]
    public function testBodylessShortcutWhenInvokedThenBodyIsNull(Closure $factory): void
    {
        /** @Given a shortcut that does not accept a body */

        /** @When the factory is called */
        $request = $factory();

        /** @Then the body is null */
        self::assertNull($request->body());
    }

    #[DataProvider('bodyShortcutWithBodyCases')]
    public function testBodyShortcutWhenBodyGivenThenBodyIsPropagated(Closure $factory, array $body): void
    {
        /** @Given a shortcut that accepts a body */

        /** @When the factory is called with a body */
        $request = $factory();

        /** @Then the body is propagated */
        self::assertSame($body, $request->body());
    }

    #[DataProvider('bodyShortcutNullBodyCases')]
    public function testBodyShortcutWhenBodyOmittedThenBodyIsNull(Closure $factory): void
    {
        /** @Given a shortcut that accepts a body */

        /** @When the factory is called without a body */
        $request = $factory();

        /** @Then the body is null */
        self::assertNull($request->body());
    }

    #[DataProvider('shortcutWithQueryCases')]
    public function testShortcutWhenQueryParametersGivenThenQueryParametersArePropagated(
        Closure $factory,
        array $queryParameters
    ): void {
        /** @Given a shortcut factory called with query parameters */

        /** @When the factory is called with query parameters */
        $request = $factory();

        /** @Then the query parameters are propagated */
        self::assertSame($queryParameters, $request->queryParameters());
    }

    #[DataProvider('shortcutWithDefaultHeadersCases')]
    public function testShortcutWhenHeadersOmittedThenHeadersDefaultsToEmptySet(Closure $factory): void
    {
        /** @Given a shortcut factory called without headers */

        /** @When the factory is called without headers */
        $request = $factory();

        /** @Then the headers default to an empty set */
        self::assertSame([], $request->headers()->toArray());
    }

    #[DataProvider('shortcutWithHeadersCases')]
    public function testShortcutWhenHeadersGivenThenHeadersIsPropagated(Closure $factory, Headers $headers): void
    {
        /** @Given a shortcut factory called with specific headers */

        /** @When the factory is called with headers */
        $request = $factory();

        /** @Then the headers are propagated unchanged */
        self::assertSame($headers, $request->headers());
    }

    public static function bodyShortcutNullBodyCases(): array
    {
        return [
            'POST'  => [static fn(): Request => Request::post(url: '/dragons')],
            'PUT'   => [static fn(): Request => Request::put(url: '/dragons')],
            'PATCH' => [static fn(): Request => Request::patch(url: '/dragons')]
        ];
    }

    public static function noBodyShortcutCases(): array
    {
        return [
            'GET'    => [static fn(): Request => Request::get(url: '/dragons')],
            'DELETE' => [static fn(): Request => Request::delete(url: '/dragons')],
            'HEAD'   => [static fn(): Request => Request::head(url: '/dragons')]
        ];
    }

    public static function bodyShortcutWithBodyCases(): array
    {
        $body = ['name' => 'Smaug', 'type' => 'fire'];

        return [
            'POST'  => [static fn(): Request => Request::post(url: '/dragons', body: $body), $body],
            'PUT'   => [static fn(): Request => Request::put(url: '/dragons', body: $body), $body],
            'PATCH' => [static fn(): Request => Request::patch(url: '/dragons', body: $body), $body]
        ];
    }

    public static function shortcutMethodCases(): array
    {
        return [
            'GET'    => [Method::GET, static fn(): Request => Request::get(url: '/dragons')],
            'POST'   => [Method::POST, static fn(): Request => Request::post(url: '/dragons')],
            'PUT'    => [Method::PUT, static fn(): Request => Request::put(url: '/dragons')],
            'PATCH'  => [Method::PATCH, static fn(): Request => Request::patch(url: '/dragons')],
            'DELETE' => [Method::DELETE, static fn(): Request => Request::delete(url: '/dragons')],
            'HEAD'   => [Method::HEAD, static fn(): Request => Request::head(url: '/dragons')]
        ];
    }

    public static function shortcutWithDefaultHeadersCases(): array
    {
        return [
            'GET'    => [static fn(): Request => Request::get(url: '/dragons')],
            'POST'   => [static fn(): Request => Request::post(url: '/dragons')],
            'PUT'    => [static fn(): Request => Request::put(url: '/dragons')],
            'PATCH'  => [static fn(): Request => Request::patch(url: '/dragons')],
            'DELETE' => [static fn(): Request => Request::delete(url: '/dragons')],
            'HEAD'   => [static fn(): Request => Request::head(url: '/dragons')]
        ];
    }

    public static function shortcutWithHeadersCases(): array
    {
        $headers = Headers::from(ContentType::applicationJson());

        return [
            'GET'    => [static fn(): Request => Request::get(url: '/dragons', headers: $headers), $headers],
            'POST'   => [static fn(): Request => Request::post(url: '/dragons', headers: $headers), $headers],
            'PUT'    => [static fn(): Request => Request::put(url: '/dragons', headers: $headers), $headers],
            'PATCH'  => [static fn(): Request => Request::patch(url: '/dragons', headers: $headers), $headers],
            'DELETE' => [static fn(): Request => Request::delete(url: '/dragons', headers: $headers), $headers],
            'HEAD'   => [static fn(): Request => Request::head(url: '/dragons', headers: $headers), $headers]
        ];
    }

    public static function shortcutWithQueryCases(): array
    {
        $queryParameters = ['sort' => 'name', 'order' => 'asc'];

        return [
            'GET'    => [
                static fn(): Request => Request::get(url: '/dragons', queryParameters: $queryParameters),
                $queryParameters
            ],
            'POST'   => [
                static fn(): Request => Request::post(url: '/dragons', queryParameters: $queryParameters),
                $queryParameters
            ],
            'PUT'    => [
                static fn(): Request => Request::put(url: '/dragons', queryParameters: $queryParameters),
                $queryParameters
            ],
            'PATCH'  => [
                static fn(): Request => Request::patch(url: '/dragons', queryParameters: $queryParameters),
                $queryParameters
            ],
            'DELETE' => [
                static fn(): Request => Request::delete(url: '/dragons', queryParameters: $queryParameters),
                $queryParameters
            ],
            'HEAD'   => [
                static fn(): Request => Request::head(url: '/dragons', queryParameters: $queryParameters),
                $queryParameters
            ]
        ];
    }
}
