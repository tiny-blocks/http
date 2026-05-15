<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Server\Request\DecodedRequest;
use TinyBlocks\Http\Internal\Server\Request\Decoder;
use TinyBlocks\Http\Method;

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
