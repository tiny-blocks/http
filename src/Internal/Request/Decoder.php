<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

use Psr\Http\Message\ServerRequestInterface;

final readonly class Decoder
{
    private function __construct(private Uri $uri, private Body $body)
    {
    }

    public static function from(ServerRequestInterface $request): Decoder
    {
        return new Decoder(
            uri: Uri::from(request: $request),
            body: Body::from(request: $request)
        );
    }

    public function decode(): DecodedRequest
    {
        return DecodedRequest::from(uri: $this->uri, body: $this->body);
    }
}
