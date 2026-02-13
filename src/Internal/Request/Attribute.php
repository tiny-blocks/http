<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

final readonly class Attribute
{
    private function __construct(private mixed $value)
    {
    }

    public static function from(mixed $value): Attribute
    {
        return new Attribute(value: $value);
    }

    public function toArray(): array
    {
        return match (true) {
            is_array($this->value) => $this->value,
            default                => []
        };
    }

    public function toFloat(): float
    {
        return match (true) {
            is_scalar($this->value) => (float)$this->value,
            default                 => 0.00
        };
    }

    public function toString(): string
    {
        return match (true) {
            is_scalar($this->value) => (string)$this->value,
            default                 => ''
        };
    }

    public function toInteger(): int
    {
        return match (true) {
            is_scalar($this->value) => (int)$this->value,
            default                 => 0
        };
    }

    public function toBoolean(): bool
    {
        return match (true) {
            is_scalar($this->value) => (bool)$this->value,
            default                 => false
        };
    }
}
