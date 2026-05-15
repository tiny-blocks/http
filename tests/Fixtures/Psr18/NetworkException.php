<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Fixtures\Psr18;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class NetworkException extends RuntimeException implements NetworkExceptionInterface
{
    public function getRequest(): RequestInterface
    {
        return new Psr17Factory()->createRequest('GET', 'https://api.example.com');
    }
}
