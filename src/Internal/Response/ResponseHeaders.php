<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response;

use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headers;

final readonly class ResponseHeaders implements Headers
{
    private function __construct(private array $headers)
    {
    }

    public static function fromOrDefault(Headers ...$headers): ResponseHeaders
    {
        $mappedHeaders = empty($headers)
            ? [ContentType::applicationJson(charset: Charset::UTF_8)->toArray()]
            : array_map(fn(Headers $header) => $header->toArray(), $headers);

        return new ResponseHeaders(headers: array_merge([], ...$mappedHeaders));
    }

    public static function fromNameAndValue(string $name, mixed $value): ResponseHeaders
    {
        return new ResponseHeaders(headers: [$name => [$value]]);
    }

    public function getByName(string $name): array
    {
        $headers = array_change_key_case($this->headers);

        return $headers[strtolower($name)] ?? [];
    }

    public function hasHeader(string $name): bool
    {
        return !empty($this->getByName(name: $name));
    }

    public function removeByName(string $name): ResponseHeaders
    {
        $headers = $this->headers;
        $existingHeader = $this->getByName(name: $name);

        if (!empty($existingHeader)) {
            unset($headers[$name]);
        }

        return new ResponseHeaders(headers: $headers);
    }

    public function toArray(): array
    {
        return $this->headers;
    }
}
