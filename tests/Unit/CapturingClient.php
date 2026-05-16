<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class CapturingClient implements ClientInterface
{
    public ?RequestInterface $captured = null;

    private function __construct(private readonly ResponseInterface $response)
    {
    }

    public static function returningStatus(int $statusCode): CapturingClient
    {
        return new CapturingClient(response: new Psr17Factory()->createResponse($statusCode));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->captured = $request;

        return $this->response;
    }
}
