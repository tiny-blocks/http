<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Random\Engine;

final readonly class FixedBytesEngine implements Engine
{
    public function __construct(private string $bytes)
    {
    }

    public function generate(): string
    {
        return $this->bytes;
    }
}
