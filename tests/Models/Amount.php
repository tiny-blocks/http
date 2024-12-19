<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Models;

final readonly class Amount
{
    public function __construct(public float $value, public Currency $currency)
    {
    }
}
