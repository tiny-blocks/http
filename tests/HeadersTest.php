<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Response;
use TinyBlocks\Http\ResponseCacheDirectives;

final class HeadersTest extends TestCase
{
    public function testResponseWithCustomHeaders(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @And by default, the response contains the 'Content-Type' header set to 'application/json; charset=utf-8' */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $response->getHeaders());

        /** @When we add custom headers to the response */
        $actual = $response
            ->withHeader(name: 'X-ID', value: 100)
            ->withHeader(name: 'X-NAME', value: 'Xpto');

        /** @Then the response should contain the correct headers */
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => [100], 'X-NAME' => ['Xpto']],
            $actual->getHeaders()
        );

        /** @And when we update the 'X-ID' header with a new value */
        $actual = $actual->withHeader(name: 'X-ID', value: 200);

        /** @Then the response should contain the updated 'X-ID' header value */
        self::assertSame('200', $actual->withAddedHeader(name: 'X-ID', value: 200)->getHeaderLine(name: 'X-ID'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => [200], 'X-NAME' => ['Xpto']],
            $actual->getHeaders()
        );

        /** @And when we remove the 'X-NAME' header */
        $actual = $actual->withoutHeader(name: 'X-NAME');

        /** @Then the response should contain only the 'X-ID' header and the default 'Content-Type' header */
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-ID' => [200]],
            $actual->getHeaders()
        );
    }

    public function testResponseWithDuplicatedHeader(): void
    {
        /** @Given an HTTP response with a 'Content-Type' header set to 'application/json; charset=utf-8' */
        $response = Response::noContent();

        /** @When we add the 'Content-Type' header twice with different values */
        $actual = $response
            ->withHeader(name: 'Content-Type', value: 'application/json; charset=utf-8')
            ->withHeader(name: 'Content-Type', value: 'application/json; charset=ISO-8859-1');

        /** @Then the response should contain the latest 'Content-Type' value */
        self::assertSame('application/json; charset=ISO-8859-1', $actual->getHeaderLine(name: 'Content-Type'));

        /** @And the headers should only contain the last 'Content-Type' value */
        self::assertSame(['Content-Type' => ['application/json; charset=ISO-8859-1']], $actual->getHeaders());
    }

    public function testResponseHeadersWithNoCustomHeader(): void
    {
        /** @Given an HTTP response with no custom headers */
        $response = Response::noContent();

        /** @When we retrieve the header that doesn't exist */
        $actual = $response->getHeader(name: 'Non-Existent-Header');

        /** @Then the header should return an empty array */
        self::assertSame([], $actual);
    }

    public function testAddHeaderAppendsDistinctValuesToExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'first');

        /** @When a distinct value is added to the same header */
        $actual = $response->withAddedHeader(name: 'X-Trace', value: 'second');

        /** @Then both values should be preserved in the original order */
        self::assertSame('first, second', $actual->getHeaderLine(name: 'X-Trace'));
        self::assertSame(['first', 'second'], $actual->getHeader(name: 'X-Trace'));
    }

    public function testAddHeaderCreatesHeaderWhenAbsent(): void
    {
        /** @Given an HTTP response without a custom header */
        $response = Response::noContent();

        /** @When a value is added for the absent header */
        $actual = $response->withAddedHeader(name: 'X-Trace', value: 'only-value');

        /** @Then the header should be created carrying the given value */
        self::assertSame(['only-value'], $actual->getHeader(name: 'X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['only-value']],
            $actual->getHeaders()
        );
    }

    public function testAddHeaderIsCaseInsensitiveWhenMatchingExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'first');

        /** @When a value is added using a differently cased header name */
        $actual = $response->withAddedHeader(name: 'x-trace', value: 'second');

        /** @Then the value should be appended preserving the original case of the header name */
        self::assertSame(['first', 'second'], $actual->getHeader(name: 'X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['first', 'second']],
            $actual->getHeaders()
        );
    }

    public function testWithoutHeaderIsCaseInsensitive(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'value');

        /** @When the header is removed using a differently cased name */
        $actual = $response->withoutHeader(name: 'x-trace');

        /** @Then the header should no longer be present */
        self::assertFalse($actual->hasHeader(name: 'X-Trace'));
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testWithoutHeaderIsNoOpWhenHeaderIsAbsent(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When the missing header is requested to be removed */
        $actual = $response->withoutHeader(name: 'X-Trace');

        /** @Then the headers should remain unchanged */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testReplaceHeaderCreatesHeaderWhenAbsent(): void
    {
        /** @Given an HTTP response without the target header */
        $response = Response::noContent();

        /** @When the header is replaced (i.e., set) */
        $actual = $response->withHeader(name: 'X-Trace', value: 'value');

        /** @Then the header should be created with the given value */
        self::assertSame(['value'], $actual->getHeader(name: 'X-Trace'));
    }

    public function testReplaceHeaderIsCaseInsensitiveOnExistingHeader(): void
    {
        /** @Given an HTTP response with a custom header */
        $response = Response::noContent()->withHeader(name: 'X-Trace', value: 'first');

        /** @When the header is replaced using a differently cased name */
        $actual = $response->withHeader(name: 'x-trace', value: 'second');

        /** @Then the original casing of the header name should be preserved and the value replaced */
        self::assertSame(['second'], $actual->getHeader(name: 'X-Trace'));
        self::assertSame(
            ['Content-Type' => ['application/json; charset=utf-8'], 'X-Trace' => ['second']],
            $actual->getHeaders()
        );
    }

    public function testMergingMultipleHeadersCombinesEntries(): void
    {
        /** @Given a Cache-Control and a Content-Type header */
        $cacheControl = CacheControl::fromResponseDirectives(noStore: ResponseCacheDirectives::noStore());
        $contentType = ContentType::textPlain();

        /** @When a response is created with both */
        $actual = Response::noContent($cacheControl, $contentType);

        /** @Then both headers should be present */
        self::assertSame(['no-store'], $actual->getHeader(name: 'Cache-Control'));
        self::assertSame(['text/plain'], $actual->getHeader(name: 'Content-Type'));
    }

    public function testResponseWithCacheControl(): void
    {
        /** @Given a Cache-Control header with multiple directives */
        $cacheControl = CacheControl::fromResponseDirectives(
            maxAge: ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000),
            noCache: ResponseCacheDirectives::noCache(),
            noStore: ResponseCacheDirectives::noStore(),
            noTransform: ResponseCacheDirectives::noTransform(),
            staleIfError: ResponseCacheDirectives::staleIfError(),
            mustRevalidate: ResponseCacheDirectives::mustRevalidate(),
            proxyRevalidate: ResponseCacheDirectives::proxyRevalidate()
        );

        /** @When we create an HTTP response with no content, using the provided Cache-Control header */
        $actual = Response::noContent($cacheControl);

        /** @And the response should include a Cache-Control header */
        self::assertTrue($actual->hasHeader(name: 'Cache-Control'));

        /** @And the Cache-Control header should match the provided directives */
        $expected = 'max-age=10000, no-cache, no-store, no-transform, stale-if-error, must-revalidate, proxy-revalidate';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Cache-Control'));
        self::assertSame([$expected], $actual->getHeader(name: 'Cache-Control'));
        self::assertSame($cacheControl->toArray(), $actual->getHeaders());
    }

    public function testResponseWithContentTypePDF(): void
    {
        /** @Given the Content-Type header is set to application/pdf */
        $contentType = ContentType::applicationPdf();

        /** @When we create an HTTP response with no content, using the provided Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response should include a Content-Type header */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));

        /** @And the Content-Type header should be set to application/pdf */
        $expected = 'application/pdf';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Content-Type'));
        self::assertSame([$expected], $actual->getHeader(name: 'Content-Type'));
        self::assertSame(['Content-Type' => [$expected]], $actual->getHeaders());
    }

    public function testResponseWithContentTypeHTML(): void
    {
        /** @Given the Content-Type header is set to text/html */
        $contentType = ContentType::textHtml();

        /** @When we create an HTTP response with no content, using the provided Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response should include a Content-Type header */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));

        /** @And the Content-Type header should be set to text/html */
        $expected = 'text/html';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Content-Type'));
        self::assertSame([$expected], $actual->getHeader(name: 'Content-Type'));
        self::assertSame(['Content-Type' => [$expected]], $actual->getHeaders());
    }

    public function testResponseWithContentTypeJSON(): void
    {
        /** @Given the Content-Type header is set to application/json */
        $contentType = ContentType::applicationJson();

        /** @When we create an HTTP response with no content, using the provided Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response should include a Content-Type header */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));

        /** @And the Content-Type header should be set to application/json */
        $expected = 'application/json';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Content-Type'));
        self::assertSame([$expected], $actual->getHeader(name: 'Content-Type'));
        self::assertSame(['Content-Type' => [$expected]], $actual->getHeaders());
    }

    public function testResponseWithContentTypePlainText(): void
    {
        /** @Given the Content-Type header is set to text/plain */
        $contentType = ContentType::textPlain();

        /** @When we create an HTTP response with no content, using the provided Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response should include a Content-Type header */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));

        /** @And the Content-Type header should be set to text/plain */
        $expected = 'text/plain';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Content-Type'));
        self::assertSame([$expected], $actual->getHeader(name: 'Content-Type'));
        self::assertSame(['Content-Type' => [$expected]], $actual->getHeaders());
    }

    public function testResponseWithContentTypeOctetStream(): void
    {
        /** @Given the Content-Type header is set to application/octet-stream */
        $contentType = ContentType::applicationOctetStream();

        /** @When we create an HTTP response with no content, using the provided Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response should include a Content-Type header */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));

        /** @And the Content-Type header should be set to application/octet-stream */
        $expected = 'application/octet-stream';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Content-Type'));
        self::assertSame([$expected], $actual->getHeader(name: 'Content-Type'));
        self::assertSame(['Content-Type' => [$expected]], $actual->getHeaders());
    }

    public function testResponseWithContentTypeFormUrlencoded(): void
    {
        /** @Given the Content-Type header is set to application/x-www-form-urlencoded */
        $contentType = ContentType::applicationFormUrlencoded();

        /** @When we create an HTTP response with no content, using the provided Content-Type */
        $actual = Response::noContent($contentType);

        /** @Then the response should include a Content-Type header */
        self::assertTrue($actual->hasHeader(name: 'Content-Type'));

        /** @And the Content-Type header should be set to application/x-www-form-urlencoded */
        $expected = 'application/x-www-form-urlencoded';

        self::assertSame($expected, $actual->getHeaderLine(name: 'Content-Type'));
        self::assertSame([$expected], $actual->getHeader(name: 'Content-Type'));
        self::assertSame(['Content-Type' => [$expected]], $actual->getHeaders());
    }
}
