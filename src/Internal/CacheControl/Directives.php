<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\CacheControl;

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#cache_directives
 */
enum Directives: string
{
    case MAX_AGE = 'max-age';
    case NO_CACHE = 'no-cache';
    case NO_STORE = 'no-store';
    case NO_TRANSFORM = 'no-transform';
    case STALE_IF_ERROR = 'stale-if-error';
    case MUST_REVALIDATE = 'must-revalidate';
    case PROXY_REVALIDATE = 'proxy-revalidate';

    public function toHeaderValue(?int $value = null): string
    {
        return match ($this) {
            self::MAX_AGE => sprintf('%s=%d', $this->value, $value),
            default       => $this->value
        };
    }
}
