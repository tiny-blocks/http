<?php

namespace TinyBlocks\Http\Internal;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;

class HttpHeadersTest extends TestCase
{
    public function testAddAndRemoveHeaders(): void
    {
        $actual = HttpHeaders::build()
            ->addFrom(key: 'X-Custom-Header', value: 'value1')
            ->addFrom(key: 'X-Custom-Header', value: 'value2')
            ->removeFrom(key: 'X-Custom-Header');

        self::assertTrue($actual->hasNoHeaders());
        self::assertFalse($actual->hasHeader(key: 'X-Custom-Header'));
    }

    public function testAddFromCode(): void
    {
        $actual = HttpHeaders::build()->addFromCode(code: HttpCode::OK);
        $expected = ['Status' => [HttpCode::OK->message()]];

        self::assertEquals($expected, $actual->toArray());
    }

    public function testAddFromContentType(): void
    {
        $headers = HttpHeaders::build()->addFromContentType(header: HttpContentType::APPLICATION_JSON);
        $actual = $headers->toArray();
        $expected = ['Content-Type' => [HttpContentType::APPLICATION_JSON->value]];

        self::assertEquals($expected, $actual);
    }

    public function testGetHeader(): void
    {
        $headers = HttpHeaders::build()
            ->addFrom(key: 'X-Custom-Header', value: 'value1')
            ->addFrom(key: 'X-Custom-Header', value: 'value2');
        $actual = $headers->getHeader(key: 'X-Custom-Header');
        $expected = ['value1', 'value2'];

        self::assertEquals($expected, $actual);
    }

    public function testToArrayWithNonUniqueValues(): void
    {
        $headers = HttpHeaders::build()
            ->addFrom(key: 'X-Custom-Header', value: 'value1')
            ->addFrom(key: 'X-Custom-Header', value: 'value1');
        $actual = $headers->toArray();
        $expected = ['X-Custom-Header' => ['value1']];

        self::assertEquals($expected, $actual);
    }
}
