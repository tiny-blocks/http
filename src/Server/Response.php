<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server;

use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Headerable;
use TinyBlocks\Http\Internal\Server\Response\InternalResponse;

final readonly class Response implements Responses
{
    public static function from(mixed $body, Code $code, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: $code, ...$headers);
    }

    public static function ok(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::OK, ...$headers);
    }

    public static function created(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::CREATED, ...$headers);
    }

    public static function accepted(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::ACCEPTED, ...$headers);
    }

    public static function noContent(Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithoutBody(code: Code::NO_CONTENT, ...$headers);
    }

    public static function badRequest(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::BAD_REQUEST, ...$headers);
    }

    public static function unauthorized(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::UNAUTHORIZED, ...$headers);
    }

    public static function forbidden(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::FORBIDDEN, ...$headers);
    }

    public static function notFound(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::NOT_FOUND, ...$headers);
    }

    public static function conflict(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::CONFLICT, ...$headers);
    }

    public static function unprocessableEntity(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::UNPROCESSABLE_ENTITY, ...$headers);
    }

    public static function internalServerError(mixed $body, Headerable ...$headers): ResponseInterface
    {
        return InternalResponse::createWithBody(body: $body, code: Code::INTERNAL_SERVER_ERROR, ...$headers);
    }
}
