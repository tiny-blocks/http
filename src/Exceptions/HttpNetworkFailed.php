<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Psr\Http\Client\NetworkExceptionInterface;
use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

final class HttpNetworkFailed extends RuntimeException implements TransportFailure
{
    private const string REASON_TEMPLATE = 'Network failure for %s %s: %s';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason,
        ?Throwable $previous = null
    ) {
        $template = HttpNetworkFailed::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $method->value, $url, $reason), previous: $previous);
    }

    /**
     * Creates an HttpNetworkFailed from a URL, HTTP method, reason, and optional previous throwable.
     *
     * @param string $url The URL of the failed request.
     * @param Method $method The HTTP method of the failed request.
     * @param string $reason The transport-level reason for the failure.
     * @param Throwable|null $previous The previous throwable preserved in the exception chain, if any.
     * @return HttpNetworkFailed The composed network-failure exception.
     */
    public static function from(
        string $url,
        Method $method,
        string $reason,
        ?Throwable $previous = null
    ): HttpNetworkFailed {
        return new HttpNetworkFailed(url: $url, method: $method, reason: $reason, previous: $previous);
    }

    /**
     * Creates an HttpNetworkFailed from a Request and a PSR-18 network exception.
     *
     * @param Request $request The outbound request that triggered the failure.
     * @param NetworkExceptionInterface $exception The PSR-18 network exception preserved as the previous throwable.
     * @return HttpNetworkFailed The composed network-failure exception wrapping the original cause.
     */
    public static function fromClientException(
        Request $request,
        NetworkExceptionInterface $exception
    ): HttpNetworkFailed {
        return HttpNetworkFailed::from(
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
