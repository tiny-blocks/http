<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

final class HttpRequestInvalid extends RuntimeException implements TransportFailure
{
    private const string REASON_TEMPLATE = 'Request is invalid for %s %s: %s';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason,
        ?Throwable $previous = null
    ) {
        $template = HttpRequestInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $method->value, $url, $reason), previous: $previous);
    }

    /**
     * Creates an HttpRequestInvalid from a URL, HTTP method, reason, and optional previous throwable.
     *
     * @param string $url The URL of the failed request.
     * @param Method $method The HTTP method of the failed request.
     * @param string $reason The transport-level reason for the failure.
     * @param Throwable|null $previous The previous throwable preserved in the exception chain, if any.
     * @return HttpRequestInvalid The composed request-invalid exception.
     */
    public static function from(
        string $url,
        Method $method,
        string $reason,
        ?Throwable $previous = null
    ): HttpRequestInvalid {
        return new HttpRequestInvalid(url: $url, method: $method, reason: $reason, previous: $previous);
    }

    /**
     * Creates an HttpRequestInvalid from a Request and a PSR-18 request exception.
     *
     * @param Request $request The outbound request that triggered the failure.
     * @param RequestExceptionInterface $exception The PSR-18 request exception preserved as the previous throwable.
     * @return HttpRequestInvalid The composed request-invalid exception wrapping the original cause.
     */
    public static function fromClientException(
        Request $request,
        RequestExceptionInterface $exception
    ): HttpRequestInvalid {
        return HttpRequestInvalid::from(
            url: $request->url(),
            method: $request->method(),
            reason: $exception->getMessage(),
            previous: $exception
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    public function method(): Method
    {
        return $this->method;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
