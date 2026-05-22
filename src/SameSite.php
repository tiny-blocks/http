<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * SameSite attribute for an HTTP Set-Cookie header controlling cross-site request behavior.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
 */
enum SameSite: string
{
    case LAX = 'Lax';
    case NONE = 'None';
    case STRICT = 'Strict';
}
