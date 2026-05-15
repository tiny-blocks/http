<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Shared;

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

    public static function fromArray(array $data): Body
    {
        return new Body(data: $data);
    }

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
                self::MAX_JSON_DEPTH,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return new Body(data: []);
        }

        return new Body(data: is_array($decoded) ? $decoded : []);
    }

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

    public function get(string $key): Attribute
    {
        $value = ($this->data[$key] ?? null);

        return Attribute::from(value: $value);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
