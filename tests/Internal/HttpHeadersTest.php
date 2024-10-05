<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;

final class HttpHeadersTest extends TestCase
{
    public function testAddAndRemoveHeaders(): void
    {
        /** @Given HttpHeaders with custom headers added */
        $actual = HttpHeaders::build()
            ->addFrom(key: 'X-Custom-Header', value: 'value1')
            ->addFrom(key: 'X-Custom-Header', value: 'value2')
            ->removeFrom(key: 'X-Custom-Header');

        /** @Then all headers should be removed */
        self::assertTrue($actual->hasNoHeaders());
        self::assertFalse($actual->hasHeader(key: 'X-Custom-Header'));
    }

    public function testAddFromCode(): void
    {
        /** @Given HttpHeaders */
        $actual = HttpHeaders::build()->addFromCode(code: HttpCode::OK);

        /** @Then the Status header should be added with the correct value */
        self::assertEquals(['Status' => [HttpCode::OK->message()]], $actual->toArray());
    }

    public function testAddFromContentType(): void
    {
        /** @Given HttpHeaders */
        $headers = HttpHeaders::build()->addFromContentType(header: HttpContentType::APPLICATION_JSON);

        /** @When adding a Content-Type header */
        $actual = $headers->toArray();

        /** @Then the Content-Type header should match the expected value */
        self::assertEquals(['Content-Type' => [HttpContentType::APPLICATION_JSON->value]], $actual);
    }

    public function testGetHeader(): void
    {
        /** @Given HttpHeaders with duplicate headers */
        $headers = HttpHeaders::build()
            ->addFrom(key: 'X-Custom-Header', value: 'value1')
            ->addFrom(key: 'X-Custom-Header', value: 'value2');

        /** @When retrieving the header */
        $actual = $headers->getHeader(key: 'X-Custom-Header');

        /** @Then the header values should match the expected array */
        self::assertEquals(['value1', 'value2'], $actual);
    }

    public function testToArrayWithNonUniqueValues(): void
    {
        /** @Given HttpHeaders with duplicate values for a single header */
        $headers = HttpHeaders::build()
            ->addFrom(key: 'X-Custom-Header', value: 'value1')
            ->addFrom(key: 'X-Custom-Header', value: 'value1');

        /** @When converting the headers to an array */
        $actual = $headers->toArray();

        /** @Then duplicate values should be collapsed into a single entry */
        self::assertEquals(['X-Custom-Header' => ['value1']], $actual);
    }
}
