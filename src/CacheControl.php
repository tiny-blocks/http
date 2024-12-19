<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Defines HTTP Cache-Control headers and their directives.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
 */
final readonly class CacheControl implements Headers
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
