<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Models;

final readonly class Dragon
{
    public function __construct(public string $name, public float $weight)
    {
    }
}
