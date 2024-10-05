<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Internal\Header;

/**
 * The Content-Type representation header is used to indicate the original media type
 * of the resource (prior to any content encoding applied for sending).
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
 */
enum HttpContentType: string implements Header
{
    case TEXT_HTML = 'text/html';
    case TEXT_PLAIN = 'text/plain';
    case APPLICATION_PDF = 'application/pdf';
    case APPLICATION_JSON = 'application/json';

    public function key(): string
    {
        return 'Content-Type';
    }

    public function value(): string
    {
        return $this->value;
    }
}
