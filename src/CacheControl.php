<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

final readonly class CacheControl implements Headerable
{
    /** @param list<string> $directives */
    private function __construct(private array $directives)
    {
    }

    public static function fromResponseDirectives(ResponseCacheDirectives ...$directives): CacheControl
    {
        $values = [];

        foreach ($directives as $directive) {
            $values[] = $directive->toString();
        }

        return new CacheControl(directives: $values);
    }

    public function toArray(): array
    {
        return ['Cache-Control' => [implode(', ', $this->directives)]];
    }
}
