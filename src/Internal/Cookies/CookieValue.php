<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Cookies;

use TinyBlocks\Http\Internal\Exceptions\CookieValueIsInvalid;

final readonly class CookieValue
{
    private const string FORBIDDEN_CHARACTERS = " \t\",;\\";

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): CookieValue
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new CookieValueIsInvalid($value);
        }

        if (strpbrk($value, self::FORBIDDEN_CHARACTERS) !== false) {
            throw new CookieValueIsInvalid($value);
        }

        return new CookieValue($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
