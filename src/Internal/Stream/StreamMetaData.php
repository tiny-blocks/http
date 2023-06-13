<?php

namespace TinyBlocks\Http\Internal\Stream;

final class StreamMetaData
{
    public function __construct(
        private readonly string $uri,
        private readonly string $mode,
        private readonly bool $seekable,
        private readonly string $streamType
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
            'mode'       => $this->getMode(),
            'seekable'   => $this->isSeekable(),
            'streamType' => $this->streamType
        ];
    }
}