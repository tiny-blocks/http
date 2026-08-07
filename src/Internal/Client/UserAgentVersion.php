<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use TinyBlocks\Http\Exceptions\UserAgentValueIsInvalid;

final readonly class UserAgentVersion
{
    private function __construct(private ?string $value)
    {
    }

    public static function from(?string $value): UserAgentVersion
    {
        if (is_null($value) || $value === '') {
            return new UserAgentVersion(value: null);
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw UserAgentValueIsInvalid::for(value: $value);
        }

        return new UserAgentVersion(value: $value);
    }

    public function toString(): ?string
    {
        return $this->value;
    }
}
