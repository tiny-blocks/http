<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

final class HttpRequestInvalid extends RuntimeException implements HttpException
{
    private const string REASON_TEMPLATE = 'Request is invalid for %s %s: %s';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason,
        ?Throwable $previous = null
    ) {
        parent::__construct(sprintf(self::REASON_TEMPLATE, $method->value, $url, $reason), 0, $previous);
    }

    public static function from(
        string $url,
        Method $method,
        string $reason,
        ?Throwable $previous = null
    ): HttpRequestInvalid {
        return new HttpRequestInvalid(url: $url, method: $method, reason: $reason, previous: $previous);
    }

    public static function fromClientException(
        Request $request,
        RequestExceptionInterface $exception
    ): HttpRequestInvalid {
        return self::from(
            url: $request->url,
            method: $request->method,
            reason: $exception->getMessage(),
            previous: $exception
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function method(): Method
    {
        return $this->method;
    }
}
