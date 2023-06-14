<?php

namespace TinyBlocks\Http;

use TinyBlocks\Http\Internal\Header;

/**
 * HTTP headers let the client and the server pass additional information with an HTTP request or response.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers
 */
final class HttpHeaders
{
    private array $values = [];

    private function __construct()
    {
    }

    public static function build(): HttpHeaders
    {
        return new HttpHeaders();
    }

    public function add(Header $header): HttpHeaders
    {
        $this->values[$header->key()][] = $header->value();

        return $this;
    }

    public function getHeader(string $key): array
    {
        return $this->values[$key] ?? [];
    }

    public function hasHeaders(): bool
    {
        return !empty($this->values);
    }

    public function hasHeader(string $key): bool
    {
        return !empty($this->getHeader(key: $key));
    }

    public function toArray(): array
    {
        return array_map(
            fn(array $values): array => [end($values)],
            array_map(fn(array $values): array => array_unique($values), $this->values)
        );
    }
}
