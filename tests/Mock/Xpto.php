<?php

namespace TinyBlocks\Http\Mock;

use TinyBlocks\Serializer\Serializer;
use TinyBlocks\Serializer\SerializerAdapter;

final class Xpto implements Serializer
{
    use SerializerAdapter;

    public function __construct(public readonly float $value)
    {
    }
}
