<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Request\DecodedRequest;
use TinyBlocks\Http\Internal\Request\Decoder;

final readonly class Request
{
    private function __construct(private ServerRequestInterface $request)
    {
    }

    public static function from(ServerRequestInterface $request): self
    {
        return new self(request: $request);
    }

    public function decode(): DecodedRequest
    {
        return Decoder::from(request: $this->request)->decode();
    }

    public function method(): Method
    {
        return Method::from(value: $this->request->getMethod());
    }
}
