<?php

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;

class HeadersTest extends TestCase
{
    public function testAddAndGetValues(): void
    {
        $headers = HttpHeaders::build()->addFromContentType(header: HttpContentType::APPLICATION_JSON);
        $expected = ['Content-Type' => [HttpContentType::APPLICATION_JSON->value]];

        self::assertEquals($expected, $headers->toArray());
    }

    public function testAddAndGetUniqueValues(): void
    {
        $headers = HttpHeaders::build()
            ->addFromContentType(header: HttpContentType::TEXT_HTML)
            ->addFromContentType(header: HttpContentType::APPLICATION_PDF);
        $expected = ['Content-Type' => [HttpContentType::APPLICATION_PDF->value]];

        self::assertEquals($expected, $headers->toArray());
    }
}
