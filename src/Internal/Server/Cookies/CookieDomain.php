<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Cookies;

use TinyBlocks\Http\Internal\Server\Exceptions\CookieDomainIsInvalid;

final readonly class CookieDomain
{
    private const string FORBIDDEN_CHARACTERS = " \t;,\"\\";

    private function __construct(private string $value)
    {
    }

    public static function from(string $value): CookieDomain
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new CookieDomainIsInvalid(domain: $value);
        }

        if (strpbrk($value, CookieDomain::FORBIDDEN_CHARACTERS) !== false) {
            throw new CookieDomainIsInvalid(domain: $value);
        }

        return new CookieDomain(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
