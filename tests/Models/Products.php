<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

use ArrayIterator;
use IteratorAggregate;
use TinyBlocks\Mapper\IterableMappability;
use TinyBlocks\Mapper\IterableMapper;
use Traversable;

/**
 * @implements IteratorAggregate<int, Product>
 */
final class Products implements IterableMapper, IteratorAggregate
{
    use IterableMappability;

    /** @var list<Product> */
    private array $elements;

    /** @param iterable<Product> $elements */
    public function __construct(iterable $elements = [])
    {
        $this->elements = is_array($elements) ? array_values($elements) : iterator_to_array($elements, false);
    }

    /** @return Traversable<int, Product> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }

    public function getType(): string
    {
        return Product::class;
    }
}
