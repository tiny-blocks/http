<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Server\Stream\StreamFactory;

final readonly class Body
{
    private const int MAX_JSON_DEPTH = 64;

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
     * Creates a Body from a PSR-7 server request, parsing JSON or falling back to the parsed body.
     *
     * @param ServerRequestInterface $request The incoming PSR-7 server request.
     * @return Body A Body carrying the decoded request payload.
     */
    public static function fromServerRequest(ServerRequestInterface $request): Body
    {
        $streamFactory = StreamFactory::fromStream(stream: $request->getBody());

        if (!$streamFactory->isEmptyContent()) {
            $decoded = json_decode($streamFactory->content(), true);

            return new Body(data: is_array($decoded) ? $decoded : []);
        }

        $parsedBody = $request->getParsedBody();

        return new Body(data: is_array($parsedBody) ? $parsedBody : []);
    }

    /**
     * Creates a Body from a PSR-7 response, decoding the JSON payload and degrading to empty on failure.
     *
     * @param ResponseInterface $response The PSR-7 response whose body is decoded.
     * @return Body A Body carrying the decoded payload, or an empty Body when decoding fails.
     */
    public static function fromResponse(ResponseInterface $response): Body
    {
        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $raw = $stream->getContents();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        try {
            $decoded = json_decode(
                $raw,
                true,
                Body::MAX_JSON_DEPTH,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return new Body(data: []);
        }

        return new Body(data: is_array($decoded) ? $decoded : []);
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
}
