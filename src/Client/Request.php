<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Method;

/**
 * Immutable outbound HTTP request carrying a URL, optional body, query parameters, method, and headers.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages
 */
final readonly class Request
{
    private function __construct(
        private string $url,
        private ?array $body,
        private Method $method,
        private Headers $headers,
        private ?array $queryParameters
    ) {
    }

    /**
     * Builds a GET request targeting the given URL.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new GET request instance.
     */
    public static function get(string $url, ?array $queryParameters = null, ?Headers $headers = null): Request
    {
        return new Request(
            url: $url,
            body: null,
            method: Method::GET,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
    }

    /**
     * Builds a POST request targeting the given URL.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, mixed>|null $body The request body as an associative array, or null when absent.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new POST request instance.
     */
    public static function post(
        string $url,
        ?array $body = null,
        ?array $queryParameters = null,
        ?Headers $headers = null
    ): Request {
        return new Request(
            url: $url,
            body: $body,
            method: Method::POST,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
    }

    /**
     * Builds a PUT request targeting the given URL.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, mixed>|null $body The request body as an associative array, or null when absent.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new PUT request instance.
     */
    public static function put(
        string $url,
        ?array $body = null,
        ?array $queryParameters = null,
        ?Headers $headers = null
    ): Request {
        return new Request(
            url: $url,
            body: $body,
            method: Method::PUT,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
    }

    /**
     * Builds a PATCH request targeting the given URL.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, mixed>|null $body The request body as an associative array, or null when absent.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new PATCH request instance.
     */
    public static function patch(
        string $url,
        ?array $body = null,
        ?array $queryParameters = null,
        ?Headers $headers = null
    ): Request {
        return new Request(
            url: $url,
            body: $body,
            method: Method::PATCH,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
    }

    /**
     * Builds a DELETE request targeting the given URL.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new DELETE request instance.
     */
    public static function delete(string $url, ?array $queryParameters = null, ?Headers $headers = null): Request
    {
        return new Request(
            url: $url,
            body: null,
            method: Method::DELETE,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
    }

    /**
     * Builds a HEAD request targeting the given URL.
     *
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new HEAD request instance.
     */
    public static function head(string $url, ?array $queryParameters = null, ?Headers $headers = null): Request
    {
        return new Request(
            url: $url,
            body: null,
            method: Method::HEAD,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
    }

    /**
     * Builds a Request for any HTTP method, including those not covered by the six shortcut factories.
     *
     * Use this factory when the target method is <code>OPTIONS</code>, <code>TRACE</code>,
     * <code>CONNECT</code>, or any other method not represented by a dedicated shortcut.
     *
     * @param Method $method The HTTP method used by the request.
     * @param string $url The URL (relative or absolute) the request targets.
     * @param array<string, mixed>|null $body The request body as an associative array, or null when absent.
     * @param array<string, scalar>|null $queryParameters The query string parameters, or null when absent.
     * @param Headers|null $headers The headers carried by the request, or null to default to an empty set.
     * @return Request A new immutable request instance.
     */
    public static function for(
        Method $method,
        string $url,
        ?array $body = null,
        ?array $queryParameters = null,
        ?Headers $headers = null
    ): Request {
        return new Request(
            url: $url,
            body: $body,
            method: $method,
            headers: $headers ?? Headers::from(),
            queryParameters: $queryParameters
        );
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
     * Returns the query parameters.
     *
     * @return array<string, scalar>|null The query string parameters, or null when absent.
     */
    public function queryParameters(): ?array
    {
        return $this->queryParameters;
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
            method: $this->method,
            headers: $this->headers,
            queryParameters: $this->queryParameters
        );
    }

    /**
     * Returns a copy of the Request with the named header replaced or appended.
     *
     * The lookup is case-insensitive, delegating to <code>Headers::with</code>: setting
     * <code>content-type</code> replaces an existing <code>Content-Type</code> entry.
     *
     * @param string $name The header name.
     * @param string $value The replacement or new header value.
     * @return Request A new instance carrying the updated header.
     */
    public function withHeader(string $name, string $value): Request
    {
        return new Request(
            url: $this->url,
            body: $this->body,
            method: $this->method,
            headers: $this->headers->with(name: $name, value: $value),
            queryParameters: $this->queryParameters
        );
    }

    /**
     * Returns a copy of the Request with the given default headers merged in.
     *
     * The merge is case-insensitive, delegating to <code>Headers::mergedWith</code>: default
     * headers whose names match existing entries regardless of casing are skipped.
     *
     * @param Headers $defaults The default headers to merge under existing entries.
     * @return Request A new instance carrying the merged headers.
     */
    public function withMergedHeaders(Headers $defaults): Request
    {
        return new Request(
            url: $this->url,
            body: $this->body,
            method: $this->method,
            headers: $this->headers->mergedWith(other: $defaults),
            queryParameters: $this->queryParameters
        );
    }

    /**
     * Returns a copy of the Request with the query parameters replaced.
     *
     * @param array<string, scalar>|null $queryParameters The replacement query string parameters,
     *   or null to clear them.
     * @return Request A new instance with the replaced query parameters.
     */
    public function withQueryParameters(?array $queryParameters): Request
    {
        return new Request(
            url: $this->url,
            body: $this->body,
            method: $this->method,
            headers: $this->headers,
            queryParameters: $queryParameters
        );
    }
}
