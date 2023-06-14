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

    public function add(Header $header): HttpHeaders
    {
        $key = $header->key();
        $this->values['header'][$key] = sprintf('%s: %s', $key, $header->value());

        return $this;
    }

    public function getHeader(): array
    {
        return $this->values['header'] ?? [];
    }

    public function hasHeaders(): bool
    {
        return !empty($this->values);
    }

    public function hasHeader(string $key): bool
    {
        return !empty($this->getHeader()[$key]);
    }

    public function toArray(): array
    {
        return $this->values;
    }
}
