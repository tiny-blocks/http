<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

use TinyBlocks\Http\Exceptions\HeaderNameIsInvalid;

final readonly class HeaderName
{
    private const string TOKEN_PATTERN = '/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/D';

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): HeaderName
    {
        if (preg_match(HeaderName::TOKEN_PATTERN, $value) !== 1) {
            throw HeaderNameIsInvalid::for(name: $value);
        }

        return new HeaderName(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
