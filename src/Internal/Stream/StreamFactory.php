<?php

namespace TinyBlocks\Http\Internal\Stream;

use Psr\Http\Message\StreamInterface;
use TinyBlocks\Serializer\Serializer;

final class StreamFactory
{
    public static function from(mixed $data): StreamInterface
    {
        $stream = Stream::from(resource: fopen('php://memory', 'wb+'));

        if ($data instanceof Serializer) {
            $stream->write(string: json_encode($data->toArray()));
            $stream->rewind();

            return $stream;
        }

        if (is_object($data)) {
            $stream->write(string: json_encode(get_object_vars($data)));
            $stream->rewind();

            return $stream;
        }

        if (is_scalar($data) || is_array($data)) {
            $stream->write(string: json_encode($data));
            $stream->rewind();

            return $stream;
        }

        $stream->write(string: '');
        $stream->rewind();

        return $stream;
    }
}
