<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Defines the contract for classes that represent HTTP headers.
 */
interface Headerable
{
    /**
     * Converts the instance to an associative array of HTTP headers.
     *
     * @return array<string, string|list<string>> An associative array where the key is the header name
     *                                            and the value is the header value (or list of values).
     */
    public function toArray(): array;
}
