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

    private function __construct(private readonly StreamMetaData $metaData, private mixed $resource)
    {
    }

    public static function from(mixed $resource): Stream
    {
        if (!is_resource($resource)) {
            throw new InvalidResource();
        }

        $metaData = StreamMetaData::from(data: stream_get_meta_data($resource));

        return new Stream(metaData: $metaData, resource: $resource);
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

        return ftell($this->resource);
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

        if (!$this->metaData->isSeekable()) {
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

        return fread($this->resource, $length);
    }

    public function write(string $string): int
    {
        if (!is_resource($this->resource)) {
            throw new NonWritableStream();
        }

        if (!$this->modeAllowsWriting()) {
            throw new NonWritableStream();
        }

        return fwrite($this->resource, $string);
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
        return is_resource($this->resource) && $this->metaData->isSeekable();
    }

    public function getContents(): string
    {
        if (!is_resource($this->resource)) {
            throw new NonReadableStream();
        }

        if (!$this->modeAllowsReading()) {
            throw new NonReadableStream();
        }

        return stream_get_contents($this->resource);
    }

    public function getMetadata(?string $key = null): mixed
    {
        $metaData = $this->metaData->toArray();

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
        $mode = $this->metaData->getMode();

        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    private function modeAllowsWriting(): bool
    {
        return strpbrk($this->metaData->getMode(), 'xwca+') !== false;
    }
}
