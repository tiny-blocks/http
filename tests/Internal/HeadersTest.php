<?php

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;

class HeadersTest extends TestCase
{
    public function testAddAndGetValues(): void
    {
        $headers = (new HttpHeaders())->add(header: HttpContentType::APPLICATION_JSON);
        $expected = ['header' => ['Content-Type' => 'Content-Type: application/json']];

        self::assertEquals($expected, $headers->toArray());
    }

    public function testAddAndGetUniqueValues(): void
    {
        $headers = (new HttpHeaders())
            ->add(header: HttpContentType::TEXT_HTML)
            ->add(header: HttpContentType::APPLICATION_PDF);

        $expected = ['header' => ['Content-Type' => 'Content-Type: application/pdf']];

        self::assertEquals($expected, $headers->toArray());
    }
}
