<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\Headers;

final class HeadersTest extends TestCase
{
    public function testFromArrayCreatesHeadersWithEntries(): void
    {
        /** @Given an array of headers */
        /** @When creating Headers from an array */
        $headers = Headers::fromArray(['Content-Type' => 'application/json', 'Accept' => 'application/json']);

        /** @Then the entries are accessible */
        self::assertSame('application/json', $headers->get('Content-Type'));
        self::assertSame('application/json', $headers->get('Accept'));
    }

    public function testFromMergesMultipleHeaderables(): void
    {
        /** @Given two headerable instances */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);
        $cookie = Cookie::create(name: 'session', value: 'abc123');

        /** @When creating Headers from multiple headerables */
        $headers = Headers::from($contentType, $cookie);

        /** @Then both header entries are present */
        self::assertTrue($headers->has('Content-Type'));
        self::assertTrue($headers->has('Set-Cookie'));
    }

    public function testFromWithNoArgumentsReturnsEmptyHeaders(): void
    {
        /** @When creating Headers with no headerable arguments */
        $headers = Headers::from();

        /** @Then the headers are empty */
        self::assertSame([], $headers->toArray());
    }

    public function testGetIsCaseInsensitive(): void
    {
        /** @Given headers with a mixed-case key */
        $headers = Headers::fromArray(['Content-Type' => 'application/json']);

        /** @When looking up with different casing */
        /** @Then the lookup succeeds */
        self::assertSame('application/json', $headers->get('content-type'));
        self::assertSame('application/json', $headers->get('CONTENT-TYPE'));
        self::assertSame('application/json', $headers->get('Content-Type'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        /** @Given headers with one entry */
        $headers = Headers::fromArray(['Content-Type' => 'application/json']);

        /** @When looking up a non-existent header */
        /** @Then null is returned */
        self::assertNull($headers->get('X-Missing'));
    }

    public function testHasIsCaseInsensitive(): void
    {
        /** @Given headers with a mixed-case key */
        $headers = Headers::fromArray(['X-Trace' => 'abc']);

        /** @When checking existence with different casing */
        /** @Then has() returns true regardless of case */
        self::assertTrue($headers->has('x-trace'));
        self::assertTrue($headers->has('X-TRACE'));
        self::assertTrue($headers->has('X-Trace'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        /** @Given empty headers */
        $headers = Headers::fromArray([]);

        /** @When checking for a non-existent header */
        /** @Then has() returns false */
        self::assertFalse($headers->has('Content-Type'));
    }

    public function testMergedWithDefaultAppearsWhenNoConflict(): void
    {
        /** @Given headers with one entry */
        $headers = Headers::fromArray(['Accept' => 'application/json']);

        /** @When merging with a default that does not conflict */
        $merged = $headers->mergedWith(defaults: ['Content-Type' => 'application/json']);

        /** @Then both entries are present */
        self::assertSame('application/json', $merged->get('Accept'));
        self::assertSame('application/json', $merged->get('Content-Type'));
    }

    public function testMergedWithExistingHeaderWinsOverDefault(): void
    {
        /** @Given headers with a Content-Type entry */
        $headers = Headers::fromArray(['Content-Type' => 'application/json; charset=utf-8']);

        /** @When merging with a default Content-Type */
        $merged = $headers->mergedWith(defaults: ['Content-Type' => 'application/json']);

        /** @Then the existing header wins */
        self::assertSame('application/json; charset=utf-8', $merged->get('Content-Type'));

        /** @And only one Content-Type entry exists in the merged result */
        self::assertCount(1, $merged->toArray());
    }

    public function testMergedWithIsCaseInsensitiveWhenCheckingConflicts(): void
    {
        /** @Given headers with a lowercase key */
        $headers = Headers::fromArray(['content-type' => 'application/json; charset=utf-8']);

        /** @When merging with a default that uses mixed casing */
        $merged = $headers->mergedWith(defaults: ['Content-Type' => 'application/json']);

        /** @Then the existing header wins despite different casing */
        self::assertSame('application/json; charset=utf-8', $merged->get('content-type'));

        /** @And only one Content-Type entry exists in the merged result */
        self::assertCount(1, $merged->toArray());
    }

    public function testToArrayReturnsAllEntries(): void
    {
        /** @Given headers with two entries */
        $headers = Headers::fromArray(['X-Trace' => 'abc', 'X-Request-ID' => '123']);

        /** @When converting to array */
        $array = $headers->toArray();

        /** @Then all entries are present */
        self::assertSame('abc', $array['X-Trace']);
        self::assertSame('123', $array['X-Request-ID']);
        self::assertCount(2, $array);
    }
}
