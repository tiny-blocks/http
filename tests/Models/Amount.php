<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

final readonly class Amount
{
    public function __construct(public float $value, public Currency $currency)
    {
    }
}
