<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Drivers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class Endpoint implements RequestHandlerInterface
{
    public function __construct(private ResponseInterface $response)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
