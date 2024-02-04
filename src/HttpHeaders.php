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

    public static function build(): HttpHeaders
    {
        return new HttpHeaders();
    }

    public function addFromCode(HttpCode $code): HttpHeaders
    {
        $template = 'HTTP/1.1 %s';
        $this->values['Status'][] = sprintf($template, $code->message());

        return $this;
    }

    public function addFromContentType(Header $header): HttpHeaders
    {
        $this->values[$header->key()][] = $header->value();

        return $this;
    }

    public function getHeader(string $key): array
    {
        return $this->values[$key] ?? [];
    }

    public function hasNoHeaders(): bool
    {
        return empty($this->values);
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
