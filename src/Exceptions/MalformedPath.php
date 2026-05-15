<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use TinyBlocks\Http\Client\Request;

final class MalformedPath extends HttpRequestFailed
{
    private const string REASON_TEMPLATE = 'Path "%s" is malformed and cannot be composed safely against a base URL.';

    public static function fromRequest(Request $request): MalformedPath
    {
        return new self(
            url: $request->url,
            method: $request->method,
            reason: sprintf(self::REASON_TEMPLATE, $request->url)
        );
    }
}
