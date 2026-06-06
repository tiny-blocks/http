<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Attribute;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Internal\Server\Request\Decoder;
use TinyBlocks\Http\Internal\Server\Stream\StreamFactory;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\Server\Decoded\DecodedRequest;
use TinyBlocks\Http\Server\Decoded\QueryParameters;

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
     * Returns the query parameters carried by the request URI.
     *
     * @return QueryParameters The QueryParameters value object built from the request.
     */
    public function query(): QueryParameters
    {
        return QueryParameters::from(request: $this->request);
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
     * Returns a single header value wrapped as a typed Attribute, or null when absent.
     *
     * @param string $name The header name to look up, case-insensitively.
     * @return Attribute|null The Attribute wrapping the folded value, or null when the header is absent.
     */
    public function header(string $name): ?Attribute
    {
        return $this->headers()->attribute(name: $name);
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

    /**
     * Returns the headers carried by the request.
     *
     * @return Headers The headers folded into a case-insensitive collection.
     */
    public function headers(): Headers
    {
        return Headers::fromMessage(message: $this->request);
    }

    /**
     * Returns the raw, undecoded request body exactly as received.
     *
     * <p>The body is read without JSON decoding, preserving the exact bytes required to verify a
     * signature computed over the raw payload. Seekable streams are rewound, so a later call to
     * {@see Request::decode()} still observes the full body.</p>
     *
     * @return string The raw request body, or an empty string when the body is empty.
     */
    public function rawBody(): string
    {
        return StreamFactory::fromStream(stream: $this->request->getBody())->content();
    }
}
