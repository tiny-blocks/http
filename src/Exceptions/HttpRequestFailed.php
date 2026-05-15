<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

class HttpRequestFailed extends RuntimeException implements HttpException
{
    protected const string JSON_ERROR_REASON_TEMPLATE = 'Failed to encode request body: %s.';

    protected function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason,
        ?Throwable $previous = null
    ) {
        parent::__construct($reason, 0, $previous);
    }

    public static function from(string $url, Method $method, string $reason, ?Throwable $previous = null): self
    {
        return new self(url: $url, method: $method, reason: $reason, previous: $previous);
    }

    public static function fromClientException(Request $request, ClientExceptionInterface $exception): self
    {
        return new self(
            url: $request->url,
            method: $request->method,
            reason: $exception->getMessage(),
            previous: $exception
        );
    }

    public static function fromJsonError(Request $request, JsonException $exception): self
    {
        return new self(
            url: $request->url,
            method: $request->method,
            reason: sprintf(self::JSON_ERROR_REASON_TEMPLATE, $exception->getMessage()),
            previous: $exception
        );
    }

    public function method(): Method
    {
        return $this->method;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function url(): string
    {
        return $this->url;
    }
}
