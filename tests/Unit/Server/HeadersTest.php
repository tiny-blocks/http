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
    public function testWithHeaderWhenInvokedThenAddsCustomHeadersAlongsideDefaultContentType(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @And by default, the response contains the 'Content-Type' header set to 'application/json; charset=utf-8' */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $response->getHeaders());

        /** @When we add custom headers to the response */
        $actual = $response
            ->withHeader(name: 'X-ID', value: '100')
            ->withHeader(name: 'X-NAME', value: 'Xpto');

        /** @Then the response contains the correct headers */
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => ['100'], 'X-NAME' => ['Xpto']],
            $actual->getHeaders()
        );

        /** @And when we update the 'X-ID' header with a new value */
        $actual = $actual->withHeader(name: 'X-ID', value: '200');

        /** @Then the response contains the updated 'X-ID' header value */
        self::assertSame('200', $actual->withAddedHeader(name: 'X-ID', value: '200')->getHeaderLine(name: 'X-ID'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => ['200'], 'X-NAME' => ['Xpto']],
            $actual->getHeaders()
        );

        /** @And when we remove the 'X-NAME' header */
        $actual = $actual->withoutHeader(name: 'X-NAME');

        /** @Then the response contains only the 'X-ID' header and the default 'Content-Type' header */
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => ['200']],
            $actual->getHeaders()
        );
    }

    public function testWithHeaderWhenSameHeaderSetTwiceThenLastValueWins(): void
    {
        /** @Given an HTTP response with a default Content-Type */
        $response = Response::noContent();

        /** @When we add the 'Content-Type' header twice with different values */
        $actual = $response
            ->withHeader(name: 'Content-Type', value: 'application/json; charset=utf-8')
            ->withHeader(name: 'Content-Type', value: 'application/json; charset=ISO-8859-1');

        /** @Then the response carries the latest 'Content-Type' value */
        self::assertSame('application/json; charset=ISO-8859-1', $actual->getHeaderLine(name: 'Content-Type'));

        /** @And only one Content-Type entry exists */
        self::assertSame(['Content-Type' => ['application/json; charset=ISO-8859-1']], $actual->getHeaders());
    }

    public function testGetHeaderWhenHeaderMissingThenReturnsEmptyArray(): void
    {
        /** @Given an HTTP response with no custom headers */
        $response = Response::noContent();

        /** @When we retrieve a missing header */
        $actual = $response->getHeader(name: 'Non-Existent-Header');

        /** @Then the header is returned as an empty array */
        self::assertSame([], $actual);
    }

    public function testWithAddedHeaderWhenDistinctValueGivenThenAppendsToExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'first');

        /** @When a distinct value is added to the same header */
        $actual = $response->withAddedHeader(name: 'X-Trace', value: 'second');

        /** @Then both values are preserved in the original order */
        self::assertSame('first, second', $actual->getHeaderLine(name: 'X-Trace'));
        self::assertSame(['first', 'second'], $actual->getHeader(name: 'X-Trace'));
    }

    public function testWithAddedHeaderWhenHeaderAbsentThenCreatesItWithGivenValue(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When a value is added for the absent header */
        $actual = $response->withAddedHeader(name: 'X-Trace', value: 'only-value');

        /** @Then the header is created carrying the given value */
        self::assertSame(['only-value'], $actual->getHeader(name: 'X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['only-value']],
            $actual->getHeaders()
        );
    }

    public function testWithAddedHeaderWhenCaseMismatchedThenMatchesExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'first');

        /** @When a value is added using a differently cased name */
        $actual = $response->withAddedHeader(name: 'x-trace', value: 'second');

        /** @Then the value is appended preserving the original case of the header name */
        self::assertSame(['first', 'second'], $actual->getHeader(name: 'X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['first', 'second']],
            $actual->getHeaders()
        );
    }

    public function testWithoutHeaderWhenCaseMismatchedThenStillRemovesHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'value');

        /** @When the header is removed using a differently cased name */
        $actual = $response->withoutHeader(name: 'x-trace');

        /** @Then the header is no longer present */
        self::assertFalse($actual->hasHeader(name: 'X-Trace'));
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testWithoutHeaderWhenAbsentThenIsNoOp(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When the missing header is requested to be removed */
        $actual = $response->withoutHeader(name: 'X-Trace');

        /** @Then the headers remain unchanged */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testWithHeaderWhenHeaderAbsentThenCreatesIt(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When the header is replaced (i.e., set) */
        $actual = $response->withHeader(name: 'X-Trace', value: 'value');

        /** @Then the header is created with the given value */
        self::assertSame(['value'], $actual->getHeader(name: 'X-Trace'));
    }

    public function testWithHeaderWhenCaseMismatchedThenReplacesExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'first');

        /** @When the header is replaced using a differently cased name */
        $actual = $response->withHeader(name: 'x-trace', value: 'second');

        /** @Then the original casing is preserved and the value replaced */
        self::assertSame(['second'], $actual->getHeader(name: 'X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['second']],
            $actual->getHeaders()
        );
    }

    public function testNoContentWhenMultipleHeaderablesGivenThenCombinesEntries(): void
    {
        /** @Given a Cache-Control and a Content-Type header */
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::noStore());
        $contentType = ContentType::textPlain();

        /** @When a response is created with both */
        $actual = Response::noContent($cacheControl, $contentType);

        /** @Then both headers are present */
        self::assertSame(['no-store'], $actual->getHeader(name: 'Cache-Control'));
        self::assertSame(['text/plain'], $actual->getHeader(name: 'Content-Type'));
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
        self::assertTrue($actual->hasHeader(name: 'Cache-Control'));

        /** @And the Cache-Control header lists every directive */
        $expected = 'max-age=10000, no-cache, no-store, no-transform, stale-if-error, '
            . 'must-revalidate, proxy-revalidate';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Cache-Control'));
        self::assertSame([$expected], $actual->getHeader(name: 'Cache-Control'));
        self::assertSame($cacheControl->toArray(), $actual->getHeaders());
    }

    public function testNoContentWhenContentTypeIsPdfThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/pdf */
        $contentType = ContentType::applicationPdf();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/pdf */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));
        self::assertSame('application/pdf', $actual->getHeaderLine(name: 'Content-Type'));
    }

    public function testNoContentWhenContentTypeIsHtmlThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to text/html */
        $contentType = ContentType::textHtml();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: text/html */
        self::assertSame('text/html', $actual->getHeaderLine(name: 'Content-Type'));
    }

    public function testNoContentWhenContentTypeIsJsonThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/json */
        $contentType = ContentType::applicationJson();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/json */
        self::assertSame('application/json', $actual->getHeaderLine(name: 'Content-Type'));
    }

    public function testNoContentWhenContentTypeIsPlainTextThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to text/plain */
        $contentType = ContentType::textPlain();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: text/plain */
        self::assertSame('text/plain', $actual->getHeaderLine(name: 'Content-Type'));
    }

    public function testNoContentWhenContentTypeIsOctetStreamThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/octet-stream */
        $contentType = ContentType::applicationOctetStream();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/octet-stream */
        self::assertSame('application/octet-stream', $actual->getHeaderLine(name: 'Content-Type'));
    }

    public function testNoContentWhenHeaderableEmitsStringValueThenWrapsItInList(): void
    {
        /** @Given a Headerable whose toArray() emits a string value (not a list) */
        $userAgent = UserAgent::from(product: 'MyApp', version: '1.2.3');

        /** @When a response is created with that header */
        $actual = Response::noContent($userAgent);

        /** @Then the header is preserved as a single-entry list */
        self::assertSame(['MyApp/1.2.3'], $actual->getHeader(name: 'User-Agent'));
    }

    public function testNoContentWhenContentTypeIsFormUrlEncodedThenHeaderReflectsIt(): void
    {
        /** @Given the Content-Type header set to application/x-www-form-urlencoded */
        $contentType = ContentType::applicationFormUrlencoded();

        /** @When the response is created with the Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response carries Content-Type: application/x-www-form-urlencoded */
        self::assertSame('application/x-www-form-urlencoded', $actual->getHeaderLine(name: 'Content-Type'));
    }
}
