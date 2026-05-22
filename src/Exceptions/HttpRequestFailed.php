<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

/**
 * Raised when the underlying PSR-18 client fails for a reason not classified as a network failure
 * or request-invalid error.
 *
 * Wraps the originating PSR-18 <code>ClientExceptionInterface</code>.
 */
final class HttpRequestFailed extends RuntimeException implements TransportFailure
{
    private const string REASON_TEMPLATE = 'PSR-18 client failed for %s %s: %s';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason,
        ?Throwable $previous = null
    ) {
        $template = HttpRequestFailed::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $method->value, $url, $reason), previous: $previous);
    }

    /**
     * Creates an HttpRequestFailed from a URL, HTTP method, reason, and optional previous throwable.
     *
     * @param string $url The URL of the failed request.
     * @param Method $method The HTTP method of the failed request.
     * @param string $reason The transport-level reason for the failure.
     * @param Throwable|null $previous The previous throwable preserved in the exception chain, if any.
     * @return HttpRequestFailed The composed request-failure exception.
     */
    public static function from(
        string $url,
        Method $method,
        string $reason,
        ?Throwable $previous = null
    ): HttpRequestFailed {
        return new HttpRequestFailed(url: $url, method: $method, reason: $reason, previous: $previous);
    }

    /**
     * Creates an HttpRequestFailed from a Request and a PSR-18 client exception.
     *
     * @param Request $request The outbound request that triggered the failure.
     * @param ClientExceptionInterface $exception The PSR-18 client exception preserved as the previous throwable.
     * @return HttpRequestFailed The composed request-failure exception wrapping the original cause.
     */
    public static function fromClientException(
        Request $request,
        ClientExceptionInterface $exception
    ): HttpRequestFailed {
        return HttpRequestFailed::from(
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
