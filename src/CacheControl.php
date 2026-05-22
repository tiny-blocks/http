<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * HTTP Cache-Control header value composed of one or more response directives.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
 */
final readonly class CacheControl implements Headerable
{
    private function __construct(private array $directives)
    {
    }

    /**
     * Creates a CacheControl from a list of response directives.
     *
     * @param ResponseCacheDirectives ...$directives The directives folded into the Cache-Control header.
     * @return CacheControl A header carrying every supplied directive in the given order.
     */
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
