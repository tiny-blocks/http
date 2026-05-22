<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Server\Request\Decoder;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\Server\Decoded\DecodedRequest;

/**
 * Typed wrapper around an incoming PSR-7 server request.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages
 */
final readonly class Request
{
    private function __construct(private ServerRequestInterface $request)
    {
    }

    /**
     * Creates a Request wrapping a PSR-7 server request.
     *
     * @param ServerRequestInterface $request The incoming PSR-7 server request.
     * @return Request A wrapper exposing typed accessors over the PSR-7 request.
     */
    public static function from(ServerRequestInterface $request): Request
    {
        return new Request(request: $request);
    }

    /**
     * Decodes the PSR-7 server request into a typed view of URI and body.
     *
     * @return DecodedRequest A decoded view exposing the URI and the parsed body.
     */
    public function decode(): DecodedRequest
    {
        return Decoder::from(request: $this->request)->decode();
    }

    /**
     * Returns the HTTP method as a typed enum.
     *
     * @return Method The HTTP method of the underlying PSR-7 request.
     */
    public function method(): Method
    {
        return Method::from($this->request->getMethod());
    }
}
