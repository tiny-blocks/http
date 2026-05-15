<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use TinyBlocks\Http\Headerable;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Method;

final readonly class Request
{
    public function __construct(
        public string $url,
        public ?array $body,
        public ?array $query,
        public Method $method,
        public Headers $headers
    ) {
    }

    public static function create(
        string $url,
        ?array $body = null,
        ?array $query = null,
        Method $method = Method::GET,
        Headerable ...$headers
    ): Request {
        return new Request(
            url: $url,
            body: $body,
            query: $query,
            method: $method,
            headers: Headers::from(...$headers)
        );
    }

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
