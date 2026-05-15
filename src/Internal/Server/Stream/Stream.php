<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Stream;

use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Internal\Server\Exceptions\InvalidResource;
use TinyBlocks\Http\Internal\Server\Exceptions\MissingResourceStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonReadableStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonSeekableStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonWritableStream;

final class Stream implements StreamInterface
{
    private const int OFFSET_ZERO = 0;

    /** @var resource|null */
    private mixed $resource;

    /** @param resource $resource */
    private function __construct(private readonly string $mode, private readonly bool $seekable, mixed $resource)
    {
        $this->resource = $resource;
    }

    public static function from(mixed $resource): Stream
    {
        if (!is_resource($resource)) {
            throw new InvalidResource();
        }

        $raw = stream_get_meta_data($resource);

        return new Stream(mode: $raw['mode'], seekable: $raw['seekable'], resource: $resource);
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

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $size = fstat($this->resource);

        return is_array($size) ? $size['size'] : null;
    }

    public function tell(): int
    {
        if (!is_resource($this->resource)) {
            throw new MissingResourceStream();
        }

        $position = ftell($this->resource);

        if ($position === false) {
            throw new MissingResourceStream();
        }

        return $position;
    }

    public function eof(): bool
    {
        return is_resource($this->resource) && feof($this->resource);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!is_resource($this->resource)) {
            throw new NonSeekableStream();
        }

        if (!$this->seekable) {
            throw new NonSeekableStream();
        }

        fseek($this->resource, $offset, $whence);
    }

    public function rewind(): void
    {
        $this->seek(offset: self::OFFSET_ZERO);
    }

    public function read(int $length): string
    {
        if (!is_resource($this->resource)) {
            throw new NonReadableStream();
        }

        if (!$this->modeAllowsReading()) {
            throw new NonReadableStream();
        }

        if ($length < 1) {
            throw new NonReadableStream();
        }

        $chunk = fread($this->resource, $length);

        return $chunk === false ? '' : $chunk;
    }

    public function write(string $string): int
    {
        if (!is_resource($this->resource)) {
            throw new NonWritableStream();
        }

        $written = $this->modeAllowsWriting() ? fwrite($this->resource, $string) : false;

        if ($written === false) {
            throw new NonWritableStream();
        }

        return $written;
    }

    public function isReadable(): bool
    {
        return is_resource($this->resource) && $this->modeAllowsReading();
    }

    public function isWritable(): bool
    {
        return is_resource($this->resource) && $this->modeAllowsWriting();
    }

    public function isSeekable(): bool
    {
        return is_resource($this->resource) && $this->seekable;
    }

    public function getContents(): string
    {
        if (!is_resource($this->resource)) {
            throw new NonReadableStream();
        }

        if (!$this->modeAllowsReading()) {
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

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    private function modeAllowsReading(): bool
    {
        return str_contains($this->mode, 'r') || str_contains($this->mode, '+');
    }

    private function modeAllowsWriting(): bool
    {
        return strpbrk($this->mode, 'xwca+') !== false;
    }
}
