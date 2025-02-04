<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response\Stream;

final readonly class StreamMetaData
{
    private function __construct(
        private string $uri,
        private string $mode,
        private bool $seekable,
        private string $streamType
    ) {
    }

    public static function from(array $data): StreamMetaData
    {
        return new StreamMetaData(
            uri: $data['uri'],
            mode: $data['mode'],
            seekable: $data['seekable'],
            streamType: $data['stream_type']
        );
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function toArray(): array
    {
        return [
            'uri'        => $this->uri,
            'mode'       => $this->mode,
            'seekable'   => $this->seekable,
            'streamType' => $this->streamType
        ];
    }
}
