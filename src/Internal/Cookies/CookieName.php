<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Cookies;

use TinyBlocks\Http\Internal\Exceptions\CookieNameIsInvalid;

final readonly class CookieName
{
    private const string TOKEN_SEPARATORS = "()<>@,;:\\\"/[]?={} \t";

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): CookieName
    {
        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new CookieNameIsInvalid($value);
        }

        if (strpbrk($value, self::TOKEN_SEPARATORS) !== false) {
            throw new CookieNameIsInvalid($value);
        }

        return new CookieName($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
