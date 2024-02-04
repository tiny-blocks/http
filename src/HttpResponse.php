<?php

namespace TinyBlocks\Http;

use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Internal\Response;

/**
 * Define HTTP response following the PSR7 specification.
 *
 * @see https://www.php-fig.org/psr/psr-7
 */
final class HttpResponse
{
    # Successful (200 – 299)

    public static function ok(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::OK, data: $data, headers: $headers);
    }

    public static function created(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::CREATED, data: $data, headers: $headers);
    }

    public static function accepted(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::ACCEPTED, data: $data, headers: $headers);
    }

    public static function noContent(?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::NO_CONTENT, data: null, headers: $headers);
    }

    # Client error (400 – 499)

    public static function badRequest(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::BAD_REQUEST, data: $data, headers: $headers);
    }

    public static function notFound(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::NOT_FOUND, data: $data, headers: $headers);
    }

    public static function conflict(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::CONFLICT, data: $data, headers: $headers);
    }

    #  Server error (500 – 599)

    public static function internalServerError(mixed $data, ?HttpHeaders $headers = null): ResponseInterface
    {
        return Response::from(code: HttpCode::INTERNAL_SERVER_ERROR, data: $data, headers: $headers);
    }
}
