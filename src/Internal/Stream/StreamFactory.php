<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Stream;

use Psr\Http\Message\StreamInterface;
use TinyBlocks\Serializer\Serializer;

final class StreamFactory
{
    public static function from(mixed $data): StreamInterface
    {
        $stream = Stream::from(resource: fopen('php://memory', 'wb+'));

        $dataToWrite = match (true) {
            is_a($data, Serializer::class)      => $data->toJson(),
            is_object($data)                    => (string)json_encode(get_object_vars($data)),
            is_scalar($data) || is_array($data) => (string)json_encode($data, JSON_PRESERVE_ZERO_FRACTION),
            default                             => ''
        };

        $stream->write(string: $dataToWrite);
        $stream->rewind();

        return $stream;
    }
}
