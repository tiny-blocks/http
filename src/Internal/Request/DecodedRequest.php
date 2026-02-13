<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

final readonly class DecodedRequest
{
    private function __construct(public Uri $uri, public Body $body)
    {
    }

    public static function from(Uri $uri, Body $body): DecodedRequest
    {
        return new DecodedRequest(uri: $uri, body: $body);
    }
}
