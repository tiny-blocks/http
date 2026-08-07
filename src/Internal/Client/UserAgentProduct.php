<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use TinyBlocks\Http\Exceptions\UserAgentProductIsEmpty;
use TinyBlocks\Http\Exceptions\UserAgentValueIsInvalid;

final readonly class UserAgentProduct
{
    private function __construct(private string $value)
    {
    }

    public static function from(string $value): UserAgentProduct
    {
        if ($value === '') {
            throw UserAgentProductIsEmpty::create();
        }

        if (preg_match('/[\x00-\x1F\x7F\/]/', $value) === 1) {
            throw UserAgentValueIsInvalid::for(value: $value);
        }

        return new UserAgentProduct(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
