<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Models;

use ArrayIterator;
use IteratorAggregate;
use TinyBlocks\Mapper\ElementType;
use TinyBlocks\Mapper\IterableMappable;
use TinyBlocks\Mapper\Mapper;
use Traversable;

#[ElementType(Product::class)]
final class Products implements IterableMappable, IteratorAggregate
{
    private array $elements;

    public function __construct(iterable $elements = [])
    {
        $this->elements = is_array($elements) ? array_values($elements) : iterator_to_array($elements, false);
    }

    public static function createFrom(iterable $elements): static
    {
        return new static(elements: $elements);
    }

    public function toJson(): string
    {
        return Mapper::create()->toJson(source: $this);
    }

    public function toArray(): array
    {
        return Mapper::create()->toArray(source: $this);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }
}
