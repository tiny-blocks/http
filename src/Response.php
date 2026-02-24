<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Internal\Response\InternalResponse;

final readonly class Response implements Responses
{
    public static function from(Code $code, mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, $code, ...$headers);
    }

    public static function ok(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::OK, ...$headers);
    }

    public static function created(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::CREATED, ...$headers);
    }

    public static function accepted(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::ACCEPTED, ...$headers);
    }

    public static function noContent(Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithoutBody(Code::NO_CONTENT, ...$headers);
    }

    public static function badRequest(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::BAD_REQUEST, ...$headers);
    }

    public static function unauthorized(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::UNAUTHORIZED, ...$headers);
    }

    public static function forbidden(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::FORBIDDEN, ...$headers);
    }

    public static function notFound(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::NOT_FOUND, ...$headers);
    }

    public static function conflict(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::CONFLICT, ...$headers);
    }

    public static function unprocessableEntity(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::UNPROCESSABLE_ENTITY, ...$headers);
    }

    public static function internalServerError(mixed $body, Headers ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody($body, Code::INTERNAL_SERVER_ERROR, ...$headers);
    }
}
