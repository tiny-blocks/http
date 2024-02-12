<?php

namespace TinyBlocks\Http;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Mock\Xpto;
use TinyBlocks\Http\Mock\Xyz;

class HttpResponseTest extends TestCase
{
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
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::OK), $response->getHeaders());
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
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::CREATED), $response->getHeaders());
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
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::ACCEPTED), $response->getHeaders());
    }

    public function testResponseNoContent(): void
    {
        $response = HttpResponse::noContent();

        self::assertEquals('', $response->getBody()->__toString());
        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(HttpCode::NO_CONTENT->value, $response->getStatusCode());
        self::assertEquals(HttpCode::NO_CONTENT->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::NO_CONTENT), $response->getHeaders());
    }

    /**
     * @dataProvider providerData
     */
    public function testResponseBadRequest(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::badRequest(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::BAD_REQUEST->value, $response->getStatusCode());
        self::assertEquals(HttpCode::BAD_REQUEST->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::BAD_REQUEST), $response->getHeaders());
    }

    /**
     * @dataProvider providerData
     */
    public function testResponseNotFound(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::notFound(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::NOT_FOUND->value, $response->getStatusCode());
        self::assertEquals(HttpCode::NOT_FOUND->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::NOT_FOUND), $response->getHeaders());
    }

    /**
     * @dataProvider providerData
     */
    public function testResponseConflict(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::conflict(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::CONFLICT->value, $response->getStatusCode());
        self::assertEquals(HttpCode::CONFLICT->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::CONFLICT), $response->getHeaders());
    }

    /**
     * @dataProvider providerData
     */
    public function testResponseInternalServerError(mixed $data, mixed $expected): void
    {
        $response = HttpResponse::internalServerError(data: $data);

        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::INTERNAL_SERVER_ERROR->value, $response->getStatusCode());
        self::assertEquals(HttpCode::INTERNAL_SERVER_ERROR->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::INTERNAL_SERVER_ERROR), $response->getHeaders());
    }

    public static function providerData(): array
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

    private function defaultHeaderFrom(HttpCode $code): array
    {
        return [
            'Status'       => [$code->message()],
            'Content-Type' => [HttpContentType::APPLICATION_JSON->value]
        ];
    }
}
