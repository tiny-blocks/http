<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Cookies;

use TinyBlocks\Http\Internal\Server\Exceptions\CookieNameIsInvalid;

final readonly class CookieName
{
    private const string TOKEN_SEPARATORS = "()<>@,;:\\\"/[]?={} \t";

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): CookieName
    {
        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new CookieNameIsInvalid(name: $value);
        }

        if (strpbrk($value, CookieName::TOKEN_SEPARATORS) !== false) {
            throw new CookieNameIsInvalid(name: $value);
        }

        return new CookieName(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
