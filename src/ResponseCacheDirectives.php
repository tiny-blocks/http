<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Internal\CacheControl\CacheControlDirective;
use TinyBlocks\Http\Internal\CacheControl\Directives;

/**
 * Represents a single Cache-Control directive for HTTP responses.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#cache_directives
 */
final readonly class ResponseCacheDirectives
{
    use CacheControlDirective;

    public static function mustRevalidate(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::MUST_REVALIDATE->toHeaderValue());
    }

    public static function proxyRevalidate(): ResponseCacheDirectives
    {
        return new ResponseCacheDirectives(value: Directives::PROXY_REVALIDATE->toHeaderValue());
    }
}
