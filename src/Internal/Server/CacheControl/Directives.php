<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\CacheControl;

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
        $template = '%s=%d';

        return match ($this) {
            Directives::MAX_AGE => sprintf($template, $this->value, $value),
            default             => $this->value
        };
    }
}
