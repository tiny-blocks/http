<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use TinyBlocks\Http\Headerable;

final readonly class RawHeader implements Headerable
{
    public function __construct(private string $name, private string $value)
    {
    }

    public function toArray(): array
    {
        return [$this->name => $this->value];
    }
}
