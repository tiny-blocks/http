<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

final class HttpRequestInvalid extends HttpRequestFailed
{
    public static function from(string $url, Method $method, string $reason, ?Throwable $previous = null): static
    {
        return new self(url: $url, method: $method, reason: $reason, previous: $previous);
    }

    public static function fromClientException(Request $request, ClientExceptionInterface $exception): static
    {
        return new self(
            url: $request->url,
            method: $request->method,
            reason: $exception->getMessage(),
            previous: $exception
        );
    }

    public static function fromJsonError(Request $request, JsonException $exception): static
    {
        return new self(
            url: $request->url,
            method: $request->method,
            reason: sprintf(self::JSON_ERROR_REASON_TEMPLATE, $exception->getMessage()),
            previous: $exception
        );
    }
}
