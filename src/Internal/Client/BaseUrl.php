<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use TinyBlocks\Http\Exceptions\BaseUrlIsInvalid;

final readonly class BaseUrl
{
    private function __construct(private string $value)
    {
    }

    public static function from(string $value): BaseUrl
    {
        if ($value === '') {
            return new BaseUrl(value: $value);
        }

        if (!preg_match('/^https?:\/\//i', $value) || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw BaseUrlIsInvalid::for(url: $value);
        }

        return new BaseUrl(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
