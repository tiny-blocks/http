<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Models\Xpto;
use TinyBlocks\Http\Models\Xyz;

final class HttpResponseTest extends TestCase
{
    #[DataProvider('providerData')]
    public function testResponseOk(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status OK */
        $response = HttpResponse::ok(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::OK->value, $response->getStatusCode());
        self::assertEquals(HttpCode::OK->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::OK), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseCreated(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Created */
        $response = HttpResponse::created(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::CREATED->value, $response->getStatusCode());
        self::assertEquals(HttpCode::CREATED->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::CREATED), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseAccepted(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Accepted */
        $response = HttpResponse::accepted(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::ACCEPTED->value, $response->getStatusCode());
        self::assertEquals(HttpCode::ACCEPTED->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::ACCEPTED), $response->getHeaders());
    }

    public function testResponseNoContent(): void
    {
        /** @Given a valid HTTP response with status No Content */
        $response = HttpResponse::noContent();

        /** @Then verify that the response body is empty and headers are correct */
        self::assertEquals('', $response->getBody()->__toString());
        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(HttpCode::NO_CONTENT->value, $response->getStatusCode());
        self::assertEquals(HttpCode::NO_CONTENT->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::NO_CONTENT), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseBadRequest(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Bad Request */
        $response = HttpResponse::badRequest(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::BAD_REQUEST->value, $response->getStatusCode());
        self::assertEquals(HttpCode::BAD_REQUEST->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::BAD_REQUEST), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseNotFound(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Not Found */
        $response = HttpResponse::notFound(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::NOT_FOUND->value, $response->getStatusCode());
        self::assertEquals(HttpCode::NOT_FOUND->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::NOT_FOUND), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseConflict(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Conflict */
        $response = HttpResponse::conflict(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::CONFLICT->value, $response->getStatusCode());
        self::assertEquals(HttpCode::CONFLICT->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::CONFLICT), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseUnprocessableEntity(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Unprocessable Entity */
        $response = HttpResponse::unprocessableEntity(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::UNPROCESSABLE_ENTITY->value, $response->getStatusCode());
        self::assertEquals(HttpCode::UNPROCESSABLE_ENTITY->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::UNPROCESSABLE_ENTITY), $response->getHeaders());
    }

    #[DataProvider('providerData')]
    public function testResponseInternalServerError(mixed $data, mixed $expected): void
    {
        /** @Given a valid HTTP response with status Internal Server Error */
        $response = HttpResponse::internalServerError(data: $data);

        /** @Then verify that the response body and headers are correct */
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals($expected, $response->getBody()->getContents());
        self::assertEquals(HttpCode::INTERNAL_SERVER_ERROR->value, $response->getStatusCode());
        self::assertEquals(HttpCode::INTERNAL_SERVER_ERROR->message(), $response->getReasonPhrase());
        self::assertEquals($this->defaultHeaderFrom(code: HttpCode::INTERNAL_SERVER_ERROR), $response->getHeaders());
    }

    public static function providerData(): array
    {
        return [
            'Null value'                            => [
                'data'     => null,
                'expected' => null
            ],
            'Empty string'                          => [
                'data'     => '',
                'expected' => '""'
            ],
            'Boolean true value'                    => [
                'data'     => true,
                'expected' => 'true'
            ],
            'Large integer value'                   => [
                'data'     => 10000000000,
                'expected' => '10000000000'
            ],
            'Xyz object serialization'              => [
                'data'     => new Xyz(value: 10),
                'expected' => '{"value":10}'
            ],
            'Xpto object serialization with toJson' => [
                'data'     => new Xpto(value: 9.99),
                'expected' => (new Xpto(value: 9.99))->toJson()
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
