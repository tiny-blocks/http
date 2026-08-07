<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Exceptions\ResponseBodyTooLarge;
use TinyBlocks\Http\Internal\BoundedContent;
use TinyBlocks\Http\Internal\JsonPayload;
use TinyBlocks\Http\Internal\Server\Stream\StreamFactory;

/**
 * Decoded payload of an HTTP message, represented as an associative array.
 *
 * Supports construction from raw arrays, PSR-7 server requests, and PSR-7 responses.
 * Individual entries are accessed as typed {@see Attribute} wrappers.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages
 */
final readonly class Body
{
    private const int DEFAULT_MAX_BYTES = (16 * 1024 * 1024);

    private function __construct(private array $data)
    {
    }

    /**
     * Creates a Body from an associative array of decoded data.
     *
     * @param array<string, mixed> $data The decoded body data.
     * @return Body A Body wrapping the supplied data.
     */
    public static function fromArray(array $data): Body
    {
        return new Body(data: $data);
    }

    /**
     * Creates a Body from a PSR-7 response, decoding the JSON payload and degrading to empty on failure.
     *
     * <p>The body is materialized into a PHP string, so a byte ceiling bounds how much of the
     * response is read. Crossing the ceiling raises {@see ResponseBodyTooLarge} and the payload
     * is never decoded. The ceiling defaults to 16 MiB when no value is supplied.</p>
     *
     * @param ResponseInterface $response The PSR-7 response whose body is decoded.
     * @param int|null $maxBytes The byte ceiling applied to the response body, or null for the 16 MiB default.
     * @return Body A Body carrying the decoded payload, or an empty Body when decoding fails.
     * @throws ResponseBodyTooLarge If the response body exceeds the byte ceiling.
     */
    public static function fromResponse(ResponseInterface $response, ?int $maxBytes = null): Body
    {
        $content = BoundedContent::from(
            stream: $response->getBody(),
            maxBytes: ($maxBytes ?? Body::DEFAULT_MAX_BYTES)
        );

        return new Body(data: JsonPayload::from(content: $content->toString())->toArray());
    }

    /**
     * Creates a Body from a PSR-7 server request, decoding the JSON payload up to 64 levels deep.
     *
     * When the raw body is empty, falls back to the parsed body and degrades to an empty Body
     * when the parsed body is not an array. JSON decoding uses <code>JSON_THROW_ON_ERROR</code>;
     * any decoding failure degrades to an empty Body rather than propagating the exception.
     *
     * @param ServerRequestInterface $request The incoming PSR-7 server request.
     * @return Body A Body carrying the decoded payload, or an empty Body when decoding fails or
     *              the payload is not an array.
     */
    public static function fromServerRequest(ServerRequestInterface $request): Body
    {
        $streamFactory = StreamFactory::fromStream(stream: $request->getBody());

        if ($streamFactory->isEmptyContent()) {
            $parsedBody = $request->getParsedBody();

            return new Body(data: is_array($parsedBody) ? $parsedBody : []);
        }

        return new Body(data: JsonPayload::from(content: $streamFactory->content())->toArray());
    }

    /**
     * Returns the Attribute associated with the given key.
     *
     * @param string $key The key to look up in the body.
     * @return Attribute The Attribute wrapping the value, or wrapping <code>null</code> when absent.
     */
    public function get(string $key): Attribute
    {
        $attributeValue = ($this->data[$key] ?? null);

        return Attribute::from(value: $attributeValue);
    }

    /**
     * Returns the Body as an associative array.
     *
     * @return array<string, mixed> The decoded body data.
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
