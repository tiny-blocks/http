<?php

namespace TinyBlocks\Http;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Mock\Xpto;
use TinyBlocks\Http\Mock\Xyz;

class HttpResponseTest extends TestCase
{
    private array $defaultHeader = [['Content-Type' => 'application/json']];

    /**
     * @dataProvider providerData
     */
    public function testResponseOk(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::ok(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::OK->value, $response->getStatusCode());
        self::assertEquals(HttpCode::OK->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeader, $response->getHeaders());
    }

    /**
     * @dataProvider providerData
     */
    public function testResponseCreated(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::created(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::CREATED->value, $response->getStatusCode());
        self::assertEquals(HttpCode::CREATED->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeader, $response->getHeaders());
    }

    /**
     * @dataProvider providerData
     */
    public function testResponseAccepted(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::accepted(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::ACCEPTED->value, $response->getStatusCode());
        self::assertEquals(HttpCode::ACCEPTED->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeader, $response->getHeaders());
    }

    public function testResponseNoContent(): void
    {
        $response = HttpResponse::noContent();

        self::assertEquals('', $response->getBody()->__toString());
        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(HttpCode::NO_CONTENT->value, $response->getStatusCode());
        self::assertEquals(HttpCode::NO_CONTENT->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeader, $response->getHeaders());
    }

    public function providerData(): array
    {
        return [
            [
                'data'     => new Xyz(value: 10),
                'expected' => '{"value":10}'
            ],
            [
                'data'     => new Xpto(value: 9.99),
                'expected' => (new Xpto(value: 9.99))->toJson()
            ],
            [
                'data'     => null,
                'expected' => null
            ],
            [
                'data'     => '',
                'expected' => '""'
            ],
            [
                'data'     => true,
                'expected' => 'true'
            ],
            [
                'data'     => 10000000000,
                'expected' => '10000000000'
            ]
        ];
    }
}
