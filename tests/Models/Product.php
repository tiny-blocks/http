<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Models;

use TinyBlocks\Serializer\Serializer;
use TinyBlocks\Serializer\SerializerAdapter;

final readonly class Product implements Serializer
{
    use SerializerAdapter;

    public function __construct(public string $name, public Amount $amount)
    {
    }
}
