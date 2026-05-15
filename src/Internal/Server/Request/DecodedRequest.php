<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Request;

use TinyBlocks\Http\Internal\Shared\Body;

final readonly class DecodedRequest
{
    private function __construct(private Uri $uri, private Body $body)
    {
    }

    public static function from(Uri $uri, Body $body): DecodedRequest
    {
        return new DecodedRequest(uri: $uri, body: $body);
    }

    public function uri(): Uri
    {
        return $this->uri;
    }

    public function body(): Body
    {
        return $this->body;
    }
}
