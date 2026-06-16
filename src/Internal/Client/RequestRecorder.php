<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use TinyBlocks\Http\Client\Request;

final class RequestRecorder
{
    private array $requests = [];

    public function all(): array
    {
        return $this->requests;
    }

    public function record(Request $request): void
    {
        $this->requests[] = $request;
    }
}
