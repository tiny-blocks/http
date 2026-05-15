<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Fixtures\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final readonly class ThrowingClient implements ClientInterface
{
    private function __construct(private Throwable $exception)
    {
    }

    public static function throwing(Throwable $exception): ThrowingClient
    {
        return new ThrowingClient(exception: $exception);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw $this->exception;
    }
}
