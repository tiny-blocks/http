<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Defines the contract for classes that represent HTTP headers.
 */
interface Headerable
{
    /**
     * Returns the Headerable as an associative map of HTTP header names to values.
     *
     * @return array<string, string|list<string>> An associative array where the key is the header
     *                                            name and the value is the header value (or list of values).
     */
    public function toArray(): array;
}
