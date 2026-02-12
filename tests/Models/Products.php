<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

use ArrayIterator;
use IteratorAggregate;
use TinyBlocks\Mapper\IterableMappability;
use TinyBlocks\Mapper\IterableMapper;
use Traversable;

final class Products implements IterableMapper, IteratorAggregate
{
    use IterableMappability;

    private array $elements;

    public function __construct(iterable $elements = [])
    {
        $this->elements = is_array($elements) ? $elements : iterator_to_array($elements);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }

    public function getType(): string
    {
        return Product::class;
    }
}
