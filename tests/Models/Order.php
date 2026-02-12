<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

use TinyBlocks\Mapper\ObjectMappability;
use TinyBlocks\Mapper\ObjectMapper;

final readonly class Order implements ObjectMapper
{
    use ObjectMappability;

    public function __construct(public int $id, public Products $products)
    {
    }
}
