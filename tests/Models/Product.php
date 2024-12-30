<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Models;

use TinyBlocks\Mapper\ObjectMappability;
use TinyBlocks\Mapper\ObjectMapper;

final readonly class Product implements ObjectMapper
{
    use ObjectMappability;

    public function __construct(public string $name, public Amount $amount)
    {
    }
}
