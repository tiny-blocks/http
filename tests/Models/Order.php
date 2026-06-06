<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

use TinyBlocks\Mapper\Mappable;
use TinyBlocks\Mapper\MappableBehavior;

final readonly class Order implements Mappable
{
    use MappableBehavior;

    public function __construct(public int $id, public Products $products)
    {
    }
}
