<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server\Decoded;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Attribute;

/**
 * Typed collection of query string parameters extracted from an HTTP request URI.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/URI
 */
final readonly class QueryParameters
{
    private function __construct(private array $data)
    {
    }

    /**
     * Creates a QueryParameters from the query parameters of a PSR-7 server request.
     *
     * @param ServerRequestInterface $request The incoming PSR-7 server request.
     * @return QueryParameters A QueryParameters carrying the request's query string parameters.
     */
    public static function from(ServerRequestInterface $request): QueryParameters
    {
        return new QueryParameters(data: $request->getQueryParams());
    }

    /**
     * Returns the QueryParameters as an associative array.
     *
     * @return array<string, mixed> The raw query parameters keyed by name.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Returns the Attribute associated with the given query key.
     *
     * @param string $key The query parameter name to look up.
     * @return Attribute The Attribute wrapping the value, or wrapping <code>null</code> when absent.
     */
    public function get(string $key): Attribute
    {
        $attributeValue = ($this->data[$key] ?? null);

        return Attribute::from(value: $attributeValue);
    }
}
