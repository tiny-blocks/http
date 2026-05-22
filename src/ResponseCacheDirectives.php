<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Internal\Server\CacheControl\Directives;

/**
 * Represents a single Cache-Control directive for HTTP responses.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#cache_directives
 */
final readonly class ResponseCacheDirectives
{
    private function __construct(private string $value)
    {
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>max-age</code> directive.
     *
     * @param int $maxAgeInWholeSeconds The maximum time in whole seconds a response may be cached.
     * @return ResponseCacheDirectives A directive instructing caches to store the response for at most the given
     *                                 number of seconds.
     */
    public static function maxAge(int $maxAgeInWholeSeconds): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::MAX_AGE->toHeaderValue(value: $maxAgeInWholeSeconds));
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>must-revalidate</code> directive.
     *
     * @return ResponseCacheDirectives A directive that forbids using a stale response.
     */
    public static function mustRevalidate(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::MUST_REVALIDATE->toHeaderValue());
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>no-cache</code> directive.
     *
     * @return ResponseCacheDirectives A directive that requires caches to validate the response with the origin
     *                                 before serving it.
     */
    public static function noCache(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::NO_CACHE->toHeaderValue());
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>no-store</code> directive.
     *
     * @return ResponseCacheDirectives A directive that forbids caches from storing any part of the response.
     */
    public static function noStore(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::NO_STORE->toHeaderValue());
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>no-transform</code> directive.
     *
     * @return ResponseCacheDirectives A directive that forbids intermediaries from transforming the response.
     */
    public static function noTransform(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::NO_TRANSFORM->toHeaderValue());
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>proxy-revalidate</code> directive.
     *
     * @return ResponseCacheDirectives A directive that forbids shared caches from using a stale response.
     */
    public static function proxyRevalidate(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::PROXY_REVALIDATE->toHeaderValue());
    }

    /**
     * Builds a ResponseCacheDirectives with the <code>stale-if-error</code> directive.
     *
     * @return ResponseCacheDirectives A directive that allows caches to serve a stale response when an error
     *                                 is encountered.
     */
    public static function staleIfError(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::STALE_IF_ERROR->toHeaderValue());
    }

    /**
     * Returns the ResponseCacheDirectives as a string.
     *
     * @return string The Cache-Control directive header value.
     */
    public function toString(): string
    {
        return $this->value;
    }
}
