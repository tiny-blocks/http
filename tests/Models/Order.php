<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Models;

use TinyBlocks\Serializer\Serializer;
use TinyBlocks\Serializer\SerializerAdapter;

final readonly class Order implements Serializer
{
    use SerializerAdapter;

    public function __construct(public int $id, public Products $products)
    {
    }
}
