<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

use TinyBlocks\Http\Exceptions\HeaderValueIsInvalid;

final readonly class HeaderValue
{
    private const string FORBIDDEN_CONTROL_PATTERN = '/[\x00-\x08\x0A-\x1F\x7F]/';

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): HeaderValue
    {
        if (preg_match(HeaderValue::FORBIDDEN_CONTROL_PATTERN, $value) === 1) {
            throw HeaderValueIsInvalid::for(value: $value);
        }

        return new HeaderValue(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
