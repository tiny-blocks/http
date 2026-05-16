<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server\Decoded;

use TinyBlocks\Http\Body;

final readonly class DecodedRequest
{
    private function __construct(private Uri $uri, private Body $body)
    {
    }

    /**
     * Creates a DecodedRequest from a Uri and a Body.
     *
     * @param Uri $uri The decoded request URI.
     * @param Body $body The decoded request body.
     * @return DecodedRequest A DecodedRequest pairing the supplied URI and body.
     */
    public static function from(Uri $uri, Body $body): DecodedRequest
    {
        return new DecodedRequest(uri: $uri, body: $body);
    }

    /**
     * Returns the uri.
     *
     * @return Uri The decoded request URI.
     */
    public function uri(): Uri
    {
        return $this->uri;
    }

    /**
     * Returns the body.
     *
     * @return Body The decoded request body.
     */
    public function body(): Body
    {
        return $this->body;
    }
}
