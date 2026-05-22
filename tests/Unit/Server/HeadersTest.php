<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Server;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\ResponseCacheDirectives;
use TinyBlocks\Http\Server\Response;
use TinyBlocks\Http\UserAgent;

final class HeadersTest extends TestCase
{
    public function testWithoutHeaderWhenAbsentThenIsNoOp(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When the missing header is requested to be removed */
        $actual = $response->withoutHeader('X-Trace');

        /** @Then the headers remain unchanged */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testWithHeaderWhenHeaderAbsentThenCreatesIt(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When the header is replaced (i.e., set) */
        $actual = $response->withHeader('X-Trace', 'value');

        /** @Then the header is created with the given value */
        self::assertSame(['value'], $actual->getHeader('X-Trace'));
    }

    public function testGetHeaderWhenHeaderMissingThenReturnsEmptyArray(): void
    {
        /** @Given an HTTP response with no custom headers */
        $response = Response::noContent();

        /** @When we retrieve a missing header */
        $actual = $response->getHeader('Non-Existent-Header');

        /** @Then the header is returned as an empty array */
        self::assertSame([], $actual);
    }

    public function testNoContentWhenContentTypeIsPdfThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/pdf */
        $contentType = ContentType::applicationPdf();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/pdf */
        self::assertTrue($actual->hasHeader('Content-Type'));
        self::assertSame('application/pdf', $actual->getHeaderLine('Content-Type'));
    }

    public function testNoContentWhenInvokedThenCarriesDefaultContentType(): void
    {
        /** @When a no-content response is created */
        $response = Response::noContent();

        /** @Then the response carries the default Content-Type header */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $response->getHeaders());
    }

    public function testWithHeaderWhenSameHeaderSetTwiceThenLastValueWins(): void
    {
        /** @Given an HTTP response with a default Content-Type */
        $response = Response::noContent();

        /** @When we add the 'Content-Type' header twice with different values */
        $actual = $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Content-Type', 'application/json; charset=ISO-8859-1');

        /** @Then the response carries the latest 'Content-Type' value */
        self::assertSame('application/json; charset=ISO-8859-1', $actual->getHeaderLine('Content-Type'));

        /** @And only one Content-Type entry exists */
        self::assertSame(['Content-Type' => ['application/json; charset=ISO-8859-1']], $actual->getHeaders());
    }

    public function testNoContentWhenContentTypeIsHtmlThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to text/html */
        $contentType = ContentType::textHtml();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: text/html */
        self::assertSame('text/html', $actual->getHeaderLine('Content-Type'));
    }

    public function testNoContentWhenContentTypeIsJsonThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/json */
        $contentType = ContentType::applicationJson();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/json */
        self::assertSame('application/json', $actual->getHeaderLine('Content-Type'));
    }

    public function testWithoutHeaderWhenCaseMismatchedThenStillRemovesHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader('X-Trace', 'value');

        /** @When the header is removed using a differently cased name */
        $actual = $response->withoutHeader('x-trace');

        /** @Then the header is no longer present */
        self::assertFalse($actual->hasHeader('X-Trace'));
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testWithHeaderWhenCaseMismatchedThenReplacesExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader('X-Trace', 'first');

        /** @When the header is replaced using a differently cased name */
        $actual = $response->withHeader('x-trace', 'second');

        /** @Then the original casing is preserved and the value replaced */
        self::assertSame(['second'], $actual->getHeader('X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['second']],
            $actual->getHeaders()
        );
    }

    public function testNoContentWhenContentTypeIsPlainTextThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to text/plain */
        $contentType = ContentType::textPlain();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: text/plain */
        self::assertSame('text/plain', $actual->getHeaderLine('Content-Type'));
    }

    public function testNoContentWhenHeaderableEmitsStringValueThenWrapsItInList(): void
    {
        /** @Given a Headerable whose toArray() emits a string value (not a list) */
        $userAgent = UserAgent::from(product: 'MyApp', version: '1.2.3');

        /** @When a response is created with that header */
        $actual = Response::noContent($userAgent);

        /** @Then the header is preserved as a single-entry list */
        self::assertSame(['MyApp/1.2.3'], $actual->getHeader('User-Agent'));
    }

    public function testNoContentWhenContentTypeIsOctetStreamThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/octet-stream */
        $contentType = ContentType::applicationOctetStream();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/octet-stream */
        self::assertSame('application/octet-stream', $actual->getHeaderLine('Content-Type'));
    }

    public function testWithAddedHeaderWhenCaseMismatchedThenMatchesExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader('X-Trace', 'first');

        /** @When a value is added using a differently cased name */
        $actual = $response->withAddedHeader('x-trace', 'second');

        /** @Then the value is appended preserving the original case of the header name */
        self::assertSame(['first', 'second'], $actual->getHeader('X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['first', 'second']],
            $actual->getHeaders()
        );
    }

    public function testWithAddedHeaderWhenHeaderAbsentThenCreatesItWithGivenValue(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When a value is added for the absent header */
        $actual = $response->withAddedHeader('X-Trace', 'only-value');

        /** @Then the header is created carrying the given value */
        self::assertSame(['only-value'], $actual->getHeader('X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['only-value']],
            $actual->getHeaders()
        );
    }

    public function testNoContentWhenContentTypeIsFormUrlEncodedThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/x-www-form-urlencoded */
        $contentType = ContentType::applicationFormUrlencoded();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/x-www-form-urlencoded */
        self::assertSame('application/x-www-form-urlencoded', $actual->getHeaderLine('Content-Type'));
    }

    public function testNoContentWhenMultipleHeaderablesGivenThenCacheControlIsPresent(): void
    {
        /** @Given a Cache-Control header */
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::noStore());

        /** @And a Content-Type header */
        $contentType = ContentType::textPlain();

        /** @When a response is created with both */
        $actual = Response::noContent($cacheControl, $contentType);

        /** @Then the Cache-Control header is present */
        self::assertSame(['no-store'], $actual->getHeader('Cache-Control'));
    }

    public function testWithAddedHeaderWhenDistinctValueGivenThenAppendsToExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader('X-Trace', 'first');

        /** @When a distinct value is added to the same header */
        $actual = $response->withAddedHeader('X-Trace', 'second');

        /** @Then both values are preserved in the original order */
        self::assertSame('first, second', $actual->getHeaderLine('X-Trace'));
        self::assertSame(['first', 'second'], $actual->getHeader('X-Trace'));
    }

    public function testNoContentWhenMultipleHeaderablesGivenThenContentTypeReplacesDefault(): void
    {
        /** @Given a Cache-Control header */
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::noStore());

        /** @And a Content-Type header */
        $contentType = ContentType::textPlain();

        /** @When a response is created with both */
        $actual = Response::noContent($cacheControl, $contentType);

        /** @Then the Content-Type header replaces the default */
        self::assertSame(['text/plain'], $actual->getHeader('Content-Type'));
    }

    public function testNoContentWhenCacheControlWithEveryDirectiveGivenThenHeaderRendersAll(): void
    {
        /** @Given a Cache-Control header with multiple directives */
        $cacheControl = CacheControl::fromResponseDirectives(
            ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000),
            ResponseCacheDirectives::noCache(),
            ResponseCacheDirectives::noStore(),
            ResponseCacheDirectives::noTransform(),
            ResponseCacheDirectives::staleIfError(),
            ResponseCacheDirectives::mustRevalidate(),
            ResponseCacheDirectives::proxyRevalidate()
        );

        /** @When we create an HTTP response with no content, using the Cache-Control header */
        $actual = Response::noContent($cacheControl);

        /** @And the response includes the Cache-Control header */
        self::assertTrue($actual->hasHeader('Cache-Control'));

        /** @And the Cache-Control header lists every directive */
        $expected = 'max-age=10000, no-cache, no-store, no-transform, stale-if-error, '
            . 'must-revalidate, proxy-revalidate';

        self::assertSame($expected, $actual->getHeaderLine('Cache-Control'));
        self::assertSame([$expected], $actual->getHeader('Cache-Control'));
        self::assertSame($cacheControl->toArray(), $actual->getHeaders());
    }

    public function testWithHeaderWhenChainedWithDistinctKeysThenBothPresentAlongsideDefault(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @When two distinct custom headers are added in a chain */
        $actual = $response
            ->withHeader('X-ID', '100')
            ->withHeader('X-NAME', 'Xpto');

        /** @Then both custom headers are present alongside the default Content-Type */
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => ['100'], 'X-NAME' => ['Xpto']],
            $actual->getHeaders()
        );
    }
}
