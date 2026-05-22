<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Stream;

use BackedEnum;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Exceptions\BodyTypeIsUnsupported;
use TinyBlocks\Mapper\Mapper;
use UnitEnum;

final readonly class StreamFactory
{
    private Stream $stream;

    private function __construct(private string $body)
    {
        $resource = fopen('php://memory', 'wb+');
        $this->stream = Stream::from(resource: $resource);
    }

    public static function fromBody(mixed $body): StreamFactory
    {
        $dataToWrite = match (true) {
            $body instanceof Mapper             => $body->toJson(),
            $body instanceof BackedEnum         => StreamFactory::toJsonFrom(body: $body->value),
            $body instanceof UnitEnum           => $body->name,
            is_object($body)                    => throw BodyTypeIsUnsupported::for(class: $body::class),
            is_string($body)                    => $body,
            is_scalar($body) || is_array($body) => StreamFactory::toJsonFrom(body: $body),
            default                             => ''
        };

        return new StreamFactory(body: $dataToWrite);
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

    private static function toJsonFrom(mixed $body): string
    {
        $encoded = json_encode($body, JSON_PRESERVE_ZERO_FRACTION);

        return $encoded === false ? '' : $encoded;
    }

    public static function fromEmptyBody(): StreamFactory
    {
        return new StreamFactory(body: '');
    }

    public function write(): StreamInterface
    {
        $this->stream->write($this->body);
        $this->stream->rewind();

        return $this->stream;
    }

    public function content(): string
    {
        return $this->body;
    }

    public function isEmptyContent(): bool
    {
        return $this->body === '';
    }
}
