<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Exceptions\ResponseBodyTooLarge;

final readonly class BoundedContent
{
    private function __construct(private string $value)
    {
    }

    public static function from(StreamInterface $stream, int $maxBytes): BoundedContent
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $content = BoundedContent::readUpTo(limit: ($maxBytes + 1), stream: $stream);

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        if (strlen($content) > $maxBytes) {
            throw ResponseBodyTooLarge::of(maxBytes: $maxBytes);
        }

        return new BoundedContent(value: $content);
    }

    private static function readUpTo(int $limit, StreamInterface $stream): string
    {
        $content = '';

        while (strlen($content) < $limit) {
            $chunk = $stream->read(($limit - strlen($content)));

            if ($chunk === '') {
                return $content;
            }

            $content .= $chunk;
        }

        return $content;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
