<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Method;

final readonly class Request
{
    private function __construct(
        private string $url,
        private ?array $body,
        private ?array $query,
        private Method $method,
        private Headers $headers
    ) {
    }

    /**
     * Builds an outbound request with the given URL, body, query, method, and headers.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, mixed>|null $body The request body as an associative array, or null when absent.
     * @param array<string, scalar>|null $query The query string parameters, or null when absent.
     * @param Method $method The HTTP method used by the request.
     * @param Headers $headers The headers folded into the request.
     * @return Request A new immutable request instance.
     */
    public static function create(
        string $url,
        ?array $body,
        ?array $query,
        Method $method,
        Headers $headers
    ): Request {
        return new Request(url: $url, body: $body, query: $query, method: $method, headers: $headers);
    }

    /**
     * Returns the url.
     *
     * @return string The URL the request targets.
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Returns the body.
     *
     * @return array<string, mixed>|null The request body, or null when absent.
     */
    public function body(): ?array
    {
        return $this->body;
    }

    /**
     * Returns the query.
     *
     * @return array<string, scalar>|null The query string parameters, or null when absent.
     */
    public function query(): ?array
    {
        return $this->query;
    }

    /**
     * Returns the method.
     *
     * @return Method The HTTP method used by the request.
     */
    public function method(): Method
    {
        return $this->method;
    }

    /**
     * Returns the headers.
     *
     * @return Headers The headers carried by the request.
     */
    public function headers(): Headers
    {
        return $this->headers;
    }

    /**
     * Returns a copy of the Request with the URL replaced.
     *
     * @param string $url The replacement URL.
     * @return Request A new instance with the replaced URL.
     */
    public function withUrl(string $url): Request
    {
        return new Request(
            url: $url,
            body: $this->body,
            query: $this->query,
            method: $this->method,
            headers: $this->headers
        );
    }

    /**
     * Returns a copy of the request carrying the given query parameters.
     *
     * @param array<string, scalar>|null $query The query string parameters, or null to clear them.
     * @return Request A new instance with the replaced query.
     */
    public function withQuery(?array $query): Request
    {
        return new Request(
            url: $this->url,
            body: $this->body,
            query: $query,
            method: $this->method,
            headers: $this->headers
        );
    }

    /**
     * Returns a copy of the Request with the given default headers merged in.
     *
     * @param Headers $defaults The default headers to merge under existing entries.
     * @return Request A new instance carrying the merged headers.
     */
    public function withMergedHeaders(Headers $defaults): Request
    {
        return new Request(
            url: $this->url,
            body: $this->body,
            query: $this->query,
            method: $this->method,
            headers: $this->headers->mergedWith(other: $defaults)
        );
    }
}
