<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Stream;

use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Internal\Server\Exceptions\MissingResourceStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonReadableStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonSeekableStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonWritableStream;

final class Stream implements StreamInterface
{
    private const int OFFSET_ZERO = 0;

    private const array READABLE_MODES = [
        'r',
        'r+',
        'rb',
        'rb+',
        'r+b',
        'w+',
        'wb+',
        'w+b',
        'a+',
        'ab+',
        'a+b',
        'x+',
        'xb+',
        'x+b',
        'c+',
        'cb+',
        'c+b',
        'rt',
        'r+t',
        'w+t',
        'a+t',
        'x+t',
        'c+t'
    ];

    private const array WRITABLE_MODES = [
        'w',
        'w+',
        'wb',
        'wb+',
        'w+b',
        'a',
        'a+',
        'ab',
        'ab+',
        'a+b',
        'x',
        'x+',
        'xb',
        'xb+',
        'x+b',
        'c',
        'c+',
        'cb',
        'cb+',
        'c+b',
        'r+',
        'r+b',
        'rb+',
        'wt',
        'w+t',
        'at',
        'a+t',
        'xt',
        'x+t',
        'ct',
        'c+t'
    ];

    private mixed $resource;

    private function __construct(private readonly bool $seekable, mixed $resource)
    {
        $this->resource = $resource;
    }

    public static function from(mixed $resource): Stream
    {
        $raw = stream_get_meta_data($resource);

        return new Stream(seekable: $raw['seekable'], resource: $resource);
    }

    public function eof(): bool
    {
        return is_resource($this->resource) && feof($this->resource);
    }

    public function read(int $length): string
    {
        if (!is_resource($this->resource)) {
            throw new NonReadableStream();
        }

        if ($length < 1) {
            throw new NonReadableStream();
        }

        $chunk = fread($this->resource, $length);

        return $chunk === false ? '' : $chunk;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!is_resource($this->resource)) {
            throw new NonSeekableStream();
        }

        fseek($this->resource, $offset, $whence);
    }

    public function tell(): int
    {
        if (!is_resource($this->resource)) {
            throw new MissingResourceStream();
        }

        return ftell($this->resource);
    }

    public function close(): void
    {
        if (!is_resource($this->resource)) {
            return;
        }

        $resource = $this->resource;
        $this->resource = null;

        fclose($resource);
    }

    public function write(string $string): int
    {
        if (!is_resource($this->resource)) {
            throw new NonWritableStream();
        }

        return fwrite($this->resource, $string);
    }

    public function detach(): mixed
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function rewind(): void
    {
        $this->seek(Stream::OFFSET_ZERO);
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $size = fstat($this->resource);

        return is_array($size) ? $size['size'] : null;
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    public function isReadable(): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $mode = stream_get_meta_data($this->resource)['mode'];

        return in_array($mode, Stream::READABLE_MODES, true);
    }

    public function isSeekable(): bool
    {
        return is_resource($this->resource) && $this->seekable;
    }

    public function isWritable(): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $mode = stream_get_meta_data($this->resource)['mode'];

        return in_array($mode, Stream::WRITABLE_MODES, true);
    }

    public function getContents(): string
    {
        if (!is_resource($this->resource)) {
            throw new NonReadableStream();
        }

        $contents = stream_get_contents($this->resource);

        return $contents === false ? '' : $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (!is_resource($this->resource)) {
            return is_null($key) ? [] : null;
        }

        $metaData = stream_get_meta_data($this->resource);

        if (is_null($key)) {
            return $metaData;
        }

        return $metaData[$key] ?? null;
    }
}
