<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Psr\Http\Message\StreamInterface;

final class ChunkedStream implements StreamInterface
{
    private int $position = 0;

    private array $requestedLengths = [];

    public function __construct(private readonly string $payload, private readonly int $chunkSize)
    {
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->payload);
    }

    public function read(int $length): string
    {
        $this->requestedLengths[] = $length;

        $chunk = substr($this->payload, $this->position, min($length, $this->chunkSize));
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = $offset;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function close(): void
    {
    }

    public function write(string $string): int
    {
        return 0;
    }

    public function detach(): mixed
    {
        return null;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function getSize(): ?int
    {
        return strlen($this->payload);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function getContents(): string
    {
        return substr($this->payload, $this->position);
    }

    public function getMetadata(?string $key = null): mixed
    {
        return null;
    }

    public function requestedLengths(): array
    {
        return $this->requestedLengths;
    }

    public function __toString(): string
    {
        return $this->payload;
    }
}
