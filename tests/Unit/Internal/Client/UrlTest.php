<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Internal\Client;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Client\Url;

final class UrlTest extends TestCase
{
    public function testBaseUrlWithTrailingSlashAndPathWithLeadingSlashProducesNoDoubleSlash(): void
    {
        /** @When composing a URL with trailing base slash and leading path slash */
        $url = Url::compose(path: '/dragons', query: [], baseUrl: 'https://api.example.com/');

        /** @Then the result has exactly one slash between host and path */
        self::assertSame('https://api.example.com/dragons', $url->value);
    }

    public function testBaseUrlWithoutTrailingSlashAndPathWithLeadingSlashProducesCorrectUrl(): void
    {
        /** @When composing a URL without trailing slash and with leading path slash */
        $url = Url::compose(path: '/dragons', query: [], baseUrl: 'https://api.example.com');

        /** @Then the result is correct without double slash */
        self::assertSame('https://api.example.com/dragons', $url->value);
    }

    public function testEmptyBaseUrlUsesRelativePathAsIs(): void
    {
        /** @When composing with no base URL and a relative path */
        $url = Url::compose(path: '/dragons', query: [], baseUrl: '');

        /** @Then the relative path is used as-is */
        self::assertSame('/dragons', $url->value);
    }

    public function testQueryParametersAreAppendedAsRfc3986(): void
    {
        /** @Given a path and query parameters */
        /** @When composing with query parameters */
        $url = Url::compose(
            path: '/dragons',
            query: ['sort' => 'name', 'order' => 'asc'],
            baseUrl: 'https://api.example.com'
        );

        /** @Then the query is appended with RFC 3986 encoding */
        self::assertStringContainsString('?sort=name&order=asc', $url->value);
    }

    public function testEmptyQueryProducesNoTrailingQuestionMark(): void
    {
        /** @When composing with an empty query array */
        $url = Url::compose(path: '/dragons', query: [], baseUrl: 'https://api.example.com');

        /** @Then the URL has no trailing question mark */
        self::assertStringNotContainsString('?', $url->value);
    }

    public function testProtocolRelativePathThrowsInvalidArgumentException(): void
    {
        /** @Then InvalidArgumentException is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When composing a path starting with // */
        Url::compose(path: '//evil.example.com/attack', query: [], baseUrl: 'https://api.example.com');
    }

    public function testProtocolRelativePathThrowsEvenWithEmptyBaseUrl(): void
    {
        /** @Then InvalidArgumentException is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When composing a protocol-relative path with an empty base URL */
        Url::compose(path: '//evil.example.com/attack', query: [], baseUrl: '');
    }

    public function testPathWithSchemeThrowsInvalidArgumentException(): void
    {
        /** @Then InvalidArgumentException is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When composing a path with https:// scheme */
        Url::compose(path: 'https://attacker.com/steal', query: [], baseUrl: 'https://api.example.com');
    }

    public function testPathWithSchemeThrowsEvenWithEmptyBaseUrl(): void
    {
        /** @Then InvalidArgumentException is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When composing a path with a scheme and empty base URL */
        Url::compose(path: 'https://attacker.com/steal', query: [], baseUrl: '');
    }

    public function testJavascriptSchemePathThrowsInvalidArgumentException(): void
    {
        /** @Then InvalidArgumentException is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When composing a path with javascript: scheme */
        Url::compose(path: 'javascript:alert(1)', query: [], baseUrl: 'https://api.example.com');
    }

    public function testPathWithControlCharactersThrowsInvalidArgumentException(): void
    {
        /** @Then InvalidArgumentException is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When composing a path containing a null byte */
        Url::compose(path: "/dragons\x00/evil", query: [], baseUrl: 'https://api.example.com');
    }

    public function testToStringReturnsSameValueAsPublicProperty(): void
    {
        /** @Given a composed URL */
        $url = Url::compose(path: '/dragons', query: [], baseUrl: 'https://api.example.com');

        /** @Then toString() returns the same value as the value property */
        self::assertSame($url->value, $url->toString());
    }
}
