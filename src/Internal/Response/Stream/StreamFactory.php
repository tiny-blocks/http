<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response\Stream;

use BackedEnum;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Mapper\Mapper;
use UnitEnum;

final readonly class StreamFactory
{
    private Stream $stream;

    private function __construct(private mixed $body)
    {
        $this->stream = Stream::from(resource: fopen('php://memory', 'wb+'));
    }

    public static function fromBody(mixed $body): StreamFactory
    {
        $dataToWrite = match (true) {
            is_a($body, Mapper::class)          => $body->toJson(),
            is_a($body, BackedEnum::class)      => self::toJsonFrom(body: $body->value),
            is_a($body, UnitEnum::class)        => $body->name,
            is_object($body)                    => self::toJsonFrom(body: get_object_vars($body)),
            is_string($body)                    => $body,
            is_scalar($body) || is_array($body) => self::toJsonFrom(body: $body),
            default                             => ''
        };

        return new StreamFactory(body: $dataToWrite);
    }

    public static function fromEmptyBody(): StreamFactory
    {
        return new StreamFactory(body: '');
    }

    public function write(): StreamInterface
    {
        $this->stream->write(string: $this->body);
        $this->stream->rewind();

        return $this->stream;
    }

    private static function toJsonFrom(mixed $body): string
    {
        return json_encode($body, JSON_PRESERVE_ZERO_FRACTION);
    }
}
