<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Models;

use ArrayIterator;
use IteratorAggregate;
use TinyBlocks\Serializer\Serializer;
use TinyBlocks\Serializer\SerializerAdapter;
use Traversable;

final class Products implements Serializer, IteratorAggregate
{
    use SerializerAdapter;

    private array $elements;

    public function __construct(iterable $elements = [])
    {
        $this->elements = is_array($elements) ? $elements : iterator_to_array($elements);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }
}
