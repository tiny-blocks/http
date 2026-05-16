<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\Headers;

final class HeadersTest extends TestCase
{
    public function testConstructorWhenEntriesGivenThenExposesEachEntry(): void
    {
        /** @Given an array of headers */
        $entries = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        /** @When creating Headers from a constructor */
        $headers = new Headers(entries: $entries);

        /** @Then the entries are accessible */
        self::assertSame('application/json', $headers->get('Content-Type'));
        self::assertSame('application/json', $headers->get('Accept'));
    }

    public function testFromWhenMultipleHeaderablesGivenThenMergesEntries(): void
    {
        /** @Given a Content-Type headerable */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And a Cookie headerable */
        $cookie = Cookie::create(name: 'session', value: 'abc123');

        /** @When creating Headers from multiple headerables */
        $headers = Headers::from($contentType, $cookie);

        /** @Then both header entries are present */
        self::assertTrue($headers->has('Content-Type'));
        self::assertTrue($headers->has('Set-Cookie'));
    }

    public function testFromWhenNoArgumentsGivenThenReturnsEmptyHeaders(): void
    {
        /** @When creating Headers with no headerable arguments */
        $headers = Headers::from();

        /** @Then the headers are empty */
        self::assertSame([], $headers->toArray());
    }

    public function testFromMessageWhenEmptyHeadersGivenThenReturnsEmptyHeaders(): void
    {
        /** @Given a PSR-7 response with no headers */
        $psrResponse = new Psr17Factory()->createResponse(200);

        /** @When building Headers from the message */
        $headers = Headers::fromMessage(message: $psrResponse);

        /** @Then the Headers instance is empty */
        self::assertSame([], $headers->toArray());
    }

    public function testFromMessageWhenMultiValueHeaderGivenThenFoldsWithComma(): void
    {
        /** @Given a PSR-7 response with a header that carries multiple values */
        $psrResponse = new Psr17Factory()->createResponse(200)
            ->withHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'text/html');

        /** @When building Headers from the message */
        $headers = Headers::fromMessage(message: $psrResponse);

        /** @Then the multi-value header is folded with a comma separator */
        self::assertSame('application/json, text/html', $headers->get('Accept'));
    }

    public function testApplyToWhenEmptyHeadersGivenThenReturnsMessageUnchanged(): void
    {
        /** @Given an empty Headers instance */
        $headers = new Headers(entries: []);

        /** @And a PSR-7 request */
        $psrRequest = new Psr17Factory()->createRequest('GET', 'https://api.example.com');

        /** @When applying the empty headers to the request */
        $applied = $headers->applyTo(message: $psrRequest);

        /** @Then the same request instance is returned without modification */
        self::assertSame($psrRequest, $applied);
    }

    public function testApplyToWhenEntriesGivenThenAttachesHeaders(): void
    {
        /** @Given a Headers instance with one entry */
        $headers = new Headers(entries: ['X-Trace' => 'abc']);

        /** @And a PSR-7 request */
        $psrRequest = new Psr17Factory()->createRequest('GET', 'https://api.example.com');

        /** @When applying the headers to the request */
        $applied = $headers->applyTo(message: $psrRequest);

        /** @Then the resulting message carries the header */
        self::assertSame('abc', $applied->getHeaderLine('X-Trace'));
    }

    public function testApplyToWhenEntriesGivenThenLeavesOriginalUnchanged(): void
    {
        /** @Given a Headers instance with one entry */
        $headers = new Headers(entries: ['X-Trace' => 'abc']);

        /** @And a PSR-7 request */
        $psrRequest = new Psr17Factory()->createRequest('GET', 'https://api.example.com');

        /** @When applying the headers to the request */
        $headers->applyTo(message: $psrRequest);

        /** @Then the original request is unchanged */
        self::assertSame('', $psrRequest->getHeaderLine('X-Trace'));
    }

    public function testGetWhenMixedCaseKeyGivenThenLookupIsCaseInsensitive(): void
    {
        /** @Given headers with a mixed-case key */
        $headers = new Headers(entries: ['Content-Type' => 'application/json']);

        /** @When looking up with different casing */
        /** @Then the lookup succeeds */
        self::assertSame('application/json', $headers->get('content-type'));
        self::assertSame('application/json', $headers->get('CONTENT-TYPE'));
        self::assertSame('application/json', $headers->get('Content-Type'));
    }

    public function testGetWhenMissingKeyGivenThenReturnsNull(): void
    {
        /** @Given headers with one entry */
        $headers = new Headers(entries: ['Content-Type' => 'application/json']);

        /** @When looking up a non-existent header */
        /** @Then null is returned */
        self::assertNull($headers->get('X-Missing'));
    }

    public function testHasWhenMixedCaseKeyGivenThenIsCaseInsensitive(): void
    {
        /** @Given headers with a mixed-case key */
        $headers = new Headers(entries: ['X-Trace' => 'abc']);

        /** @When checking existence with different casing */
        /** @Then has() returns true regardless of case */
        self::assertTrue($headers->has('x-trace'));
        self::assertTrue($headers->has('X-TRACE'));
        self::assertTrue($headers->has('X-Trace'));
    }

    public function testHasWhenMissingKeyGivenThenReturnsFalse(): void
    {
        /** @Given empty headers */
        $headers = new Headers(entries: []);

        /** @When checking for a non-existent header */
        /** @Then has() returns false */
        self::assertFalse($headers->has('Content-Type'));
    }

    public function testMergedWithWhenOtherHasNewEntriesThenBothAppearInResult(): void
    {
        /** @Given headers with one entry */
        $headers = new Headers(entries: ['Accept' => 'application/json']);

        /** @When merging with a Headers carrying a default that does not conflict */
        $merged = $headers->mergedWith(other: new Headers(entries: ['Content-Type' => 'application/json']));

        /** @Then both entries are present */
        self::assertSame('application/json', $merged->get('Accept'));
        self::assertSame('application/json', $merged->get('Content-Type'));
    }

    public function testMergedWithWhenOtherCollidesThenExistingEntryWins(): void
    {
        /** @Given headers with a Content-Type entry */
        $headers = new Headers(entries: ['Content-Type' => 'application/json; charset=utf-8']);

        /** @When merging with a Headers carrying a default Content-Type */
        $merged = $headers->mergedWith(other: new Headers(entries: ['Content-Type' => 'application/json']));

        /** @Then the existing header wins */
        self::assertSame('application/json; charset=utf-8', $merged->get('Content-Type'));

        /** @And only one Content-Type entry exists in the merged result */
        self::assertCount(1, $merged->toArray());
    }

    public function testMergedWithWhenCasingDiffersThenStillTreatsAsCollision(): void
    {
        /** @Given headers with a lowercase key */
        $headers = new Headers(entries: ['content-type' => 'application/json; charset=utf-8']);

        /** @When merging with a Headers using mixed casing */
        $merged = $headers->mergedWith(other: new Headers(entries: ['Content-Type' => 'application/json']));

        /** @Then the existing header wins despite different casing */
        self::assertSame('application/json; charset=utf-8', $merged->get('content-type'));

        /** @And only one Content-Type entry exists in the merged result */
        self::assertCount(1, $merged->toArray());
    }

    public function testToArrayWhenMultipleEntriesGivenThenReturnsAll(): void
    {
        /** @Given headers with two entries */
        $headers = new Headers(entries: ['X-Trace' => 'abc', 'X-Request-ID' => '123']);

        /** @When converting to array */
        $array = $headers->toArray();

        /** @Then all entries are present */
        self::assertSame('abc', $array['X-Trace']);
        self::assertSame('123', $array['X-Request-ID']);
        self::assertCount(2, $array);
    }
}
