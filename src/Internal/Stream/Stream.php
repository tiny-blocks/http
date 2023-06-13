<?php

namespace TinyBlocks\Http\Internal\Stream;

use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Internal\Exceptions\MissingResourceStream;
use TinyBlocks\Http\Internal\Exceptions\NonReadableStream;
use TinyBlocks\Http\Internal\Exceptions\NonSeekableStream;
use TinyBlocks\Http\Internal\Exceptions\NonWritableStream;

final class Stream implements StreamInterface
{
    private string $content = '';
    private bool $contentFetched = false;

    private function __construct(private mixed $resource, private readonly StreamMetaData $metaData)
    {
    }

    public static function from(mixed $resource): Stream
    {
        $metaData = StreamMetaData::from(data: stream_get_meta_data($resource));

        return new Stream(resource: $resource, metaData: $metaData);
    }

    public function close(): void
    {
        if ($this->noResource()) {
            return;
        }

        $resource = $this->detach();

        fclose($resource);
    }

    public function detach(): mixed
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->noResource()) {
            return null;
        }

        return intval(fstat($this->resource)['size']);
    }

    public function tell(): int
    {
        if ($this->noResource()) {
            throw new MissingResourceStream();
        }

        return ftell($this->resource);
    }

    public function eof(): bool
    {
        return $this->resource && feof($this->resource);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new NonSeekableStream();
        }

        fseek($this->resource, $offset, $whence);
    }

    public function rewind(): void
    {
        $this->seek(offset: 0);
    }

    public function read(int $length): string
    {
        if (!$this->isReadable()) {
            throw new NonReadableStream();
        }

        return fread($this->resource, $length);
    }

    public function write(string $string): int
    {
        if (!$this->isWritable()) {
            throw new NonWritableStream();
        }

        return fwrite($this->resource, $string);
    }

    public function isReadable(): bool
    {
        if ($this->noResource()) {
            return false;
        }

        $mode = $this->metaData->getMode();

        return $mode === 'r' || strstr($mode, '+');
    }

    public function isWritable(): bool
    {
        if ($this->noResource()) {
            return false;
        }

        $mode = $this->metaData->getMode();

        return strstr($mode, 'x')
            || strstr($mode, 'w')
            || strstr($mode, 'c')
            || strstr($mode, 'a')
            || strstr($mode, '+');
    }

    public function isSeekable(): bool
    {
        return !$this->noResource() && $this->metaData->isSeekable();
    }

    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new NonReadableStream();
        }

        if (!$this->contentFetched) {
            $this->content = stream_get_contents($this->resource);
            $this->contentFetched = true;
        }

        return $this->content;
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

    private function noResource(): bool
    {
        return empty($this->resource);
    }
}
