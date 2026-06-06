<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

use TinyBlocks\Mapper\Mappable;
use TinyBlocks\Mapper\MappableBehavior;

final readonly class Product implements Mappable
{
    use MappableBehavior;

    public function __construct(public string $name, public Amount $amount)
    {
    }
}
