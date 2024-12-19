<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\CacheControl;

trait CacheControlDirective
{
    private function __construct(private readonly string $value)
    {
    }

    public static function maxAge(int $maxAgeInWholeSeconds): static
    {
        return new self(value: Directives::MAX_AGE->toHeaderValue(value: $maxAgeInWholeSeconds));
    }

    public static function noCache(): static
    {
        return new self(value: Directives::NO_CACHE->toHeaderValue());
    }

    public static function noStore(): static
    {
        return new self(value: Directives::NO_STORE->toHeaderValue());
    }

    public static function noTransform(): static
    {
        return new self(value: Directives::NO_TRANSFORM->toHeaderValue());
    }

    public static function staleIfError(): static
    {
        return new self(value: Directives::STALE_IF_ERROR->toHeaderValue());
    }

    public function toString(): string
    {
        return $this->value;
    }
}
