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
    public static function ok(mixed $data, array $headers = []): ResponseInterface
    {
        return Response::from(code: HttpCode::OK, data: $data, headers: $headers);
    }

    public static function created(mixed $data, array $headers = []): ResponseInterface
    {
        return Response::from(code: HttpCode::CREATED, data: $data, headers: $headers);
    }

    public static function accepted(mixed $data, array $headers = []): ResponseInterface
    {
        return Response::from(code: HttpCode::ACCEPTED, data: $data, headers: $headers);
    }

    public static function noContent(array $headers = []): ResponseInterface
    {
        return Response::from(code: HttpCode::NO_CONTENT, data: null, headers: $headers);
    }
}
