<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ScriptedClient implements ClientInterface
{
    private array $outcomes = [];
    private array $requests = [];

    public function answers(ResponseInterface|Throwable ...$outcomes): void
    {
        $this->outcomes = $outcomes;
    }

    public function requests(): array
    {
        return $this->requests;
    }

    public function requestAt(int $index): RequestInterface
    {
        return $this->requests[$index];
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $outcome = array_shift($this->outcomes);

        if (is_null($outcome)) {
            throw new LogicException(message: 'No scripted outcome left.');
        }

        if ($outcome instanceof Throwable) {
            throw $outcome;
        }

        return $outcome;
    }
}
