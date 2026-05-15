<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

final readonly class CacheControl implements Headerable
{
    private function __construct(private array $directives)
    {
    }

    public static function fromResponseDirectives(ResponseCacheDirectives ...$directives): CacheControl
    {
        $mapper = fn(ResponseCacheDirectives $directive) => $directive->toString();

        return new CacheControl(directives: array_map($mapper, $directives));
    }

    public function toArray(): array
    {
        return ['Cache-Control' => [implode(', ', $this->directives)]];
    }
}
