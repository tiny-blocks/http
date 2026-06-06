<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\MimeType;

final class ContentTypeTest extends TestCase
{
    public function testToArrayWhenNoCharsetGivenThenReturnsHeaderMap(): void
    {
        /** @Given a ContentType without a charset */
        $contentType = ContentType::applicationJson();

        /** @When converting it to the header map */
        $actual = $contentType->toArray();

        /** @Then the map carries the bare media type */
        self::assertSame(['Content-Type' => ['application/json']], $actual);
    }

    public function testToStringWhenNoCharsetGivenThenReturnsBareMediaType(): void
    {
        /** @Given a ContentType without a charset */
        $contentType = ContentType::applicationJson();

        /** @When rendering it as a header value */
        $actual = $contentType->toString();

        /** @Then the bare media type is returned */
        self::assertSame('application/json', $actual);
    }

    public function testToStringWhenCharsetGivenThenAppendsCharsetParameter(): void
    {
        /** @Given a ContentType carrying a charset */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @When rendering it as a header value */
        $actual = $contentType->toString();

        /** @Then the charset parameter is appended to the media type */
        self::assertSame('application/json; charset=utf-8', $actual);
    }

    public function testToArrayWhenCharsetGivenThenReturnsHeaderMapWithCharset(): void
    {
        /** @Given a ContentType carrying a charset */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @When converting it to the header map */
        $actual = $contentType->toArray();

        /** @Then the map carries the media type with the charset parameter */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual);
    }

    public function testMimeTypeWhenJsonContentTypeGivenThenReturnsApplicationJsonMimeType(): void
    {
        /** @Given a ContentType for application/json */
        $contentType = ContentType::applicationJson();

        /** @When asking for its MIME type */
        $actual = $contentType->mimeType();

        /** @Then the application/json MIME type is returned */
        self::assertSame(MimeType::APPLICATION_JSON, $actual);
    }

    public function testMimeTypeWhenCharsetGivenThenReturnsMimeTypeWithoutCharset(): void
    {
        /** @Given a ContentType for text/plain carrying a charset */
        $contentType = ContentType::textPlain(charset: Charset::UTF_8);

        /** @When asking for its MIME type */
        $actual = $contentType->mimeType();

        /** @Then the bare text/plain MIME type is returned regardless of the charset */
        self::assertSame(MimeType::TEXT_PLAIN, $actual);
    }
}
