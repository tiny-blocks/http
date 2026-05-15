<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Stream;

use BackedEnum;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Mapper\Mapper;
use UnitEnum;

final readonly class StreamFactory
{
    private Stream $stream;

    private function __construct(private string $body)
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'wb+');
        $this->stream = Stream::from(resource: $resource);
    }

    public static function fromBody(mixed $body): StreamFactory
    {
        $dataToWrite = match (true) {
            $body instanceof Mapper => $body->toJson(),
            $body instanceof BackedEnum => self::toJsonFrom(body: $body->value),
            $body instanceof UnitEnum => $body->name,
            is_object($body) => self::toJsonFrom(body: get_object_vars($body)),
            is_string($body) => $body,
            is_scalar($body) || is_array($body) => self::toJsonFrom(body: $body),
            default => ''
        };

        return new StreamFactory(body: $dataToWrite);
    }

    public static function fromEmptyBody(): StreamFactory
    {
        return new StreamFactory(body: '');
    }

    public static function fromStream(StreamInterface $stream): StreamFactory
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $body = $stream->getContents();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return new StreamFactory(body: $body);
    }

    public function content(): string
    {
        return $this->body;
    }

    public function isEmptyContent(): bool
    {
        return $this->body === '';
    }

    public function write(): StreamInterface
    {
        $this->stream->write(string: $this->body);
        $this->stream->rewind();

        return $this->stream;
    }

    private static function toJsonFrom(mixed $body): string
    {
        $encoded = json_encode($body, JSON_PRESERVE_ZERO_FRACTION);

        return $encoded === false ? '' : $encoded;
    }
}
