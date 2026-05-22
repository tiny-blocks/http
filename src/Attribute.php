<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Typed wrapper around a scalar or array value extracted from an HTTP message.
 *
 * Provides coercion methods that convert the wrapped value to a requested primitive type,
 * falling back to a safe zero-value when conversion is not possible.
 */
final readonly class Attribute
{
    private function __construct(private mixed $value)
    {
    }

    /**
     * Creates an Attribute wrapping the given value.
     *
     * @param mixed $value The value carried by the Attribute.
     * @return Attribute An Attribute wrapping the supplied value.
     */
    public static function from(mixed $value): Attribute
    {
        return new Attribute(value: $value);
    }

    /**
     * Returns the Attribute as an array.
     *
     * @return array<int|string, mixed> The wrapped value when it is an array, otherwise an empty array.
     */
    public function toArray(): array
    {
        return match (true) {
            is_array($this->value) => $this->value,
            default                => []
        };
    }

    /**
     * Returns the Attribute as a float.
     *
     * @return float The wrapped value coerced to a float, or <code>0.00</code> when it is not scalar.
     */
    public function toFloat(): float
    {
        return match (true) {
            is_scalar($this->value) => (float)$this->value,
            default                 => 0.00
        };
    }

    /**
     * Returns the Attribute as a string.
     *
     * @return string The wrapped value coerced to a string, or an empty string when it is not scalar.
     */
    public function toString(): string
    {
        return match (true) {
            is_scalar($this->value) => (string)$this->value,
            default                 => ''
        };
    }

    /**
     * Returns the Attribute as a boolean.
     *
     * @return bool The wrapped value coerced to a boolean, or <code>false</code> when it is not scalar.
     */
    public function toBoolean(): bool
    {
        return match (true) {
            is_scalar($this->value) => (bool)$this->value,
            default                 => false
        };
    }

    /**
     * Returns the Attribute as an integer.
     *
     * @return int The wrapped value coerced to an integer, or <code>0</code> when it is not scalar.
     */
    public function toInteger(): int
    {
        return match (true) {
            is_scalar($this->value) => (int)$this->value,
            default                 => 0
        };
    }
}
