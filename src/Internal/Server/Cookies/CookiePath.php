<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Cookies;

use TinyBlocks\Http\Internal\Server\Exceptions\CookiePathIsInvalid;

final readonly class CookiePath
{
    private const string FORBIDDEN_CHARACTERS = ";,";

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): CookiePath
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new CookiePathIsInvalid(path: $value);
        }

        if (strpbrk($value, CookiePath::FORBIDDEN_CHARACTERS) !== false) {
            throw new CookiePathIsInvalid(path: $value);
        }

        return new CookiePath(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
