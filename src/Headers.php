<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Defines the contract for classes that represent HTTP headers.
 */
interface Headers
{
    /**
     * Converts the instance to an associative array of HTTP headers.
     *
     * @return array An associative array where the key is the header name
     *               and the value is the header value.
     */
    public function toArray(): array;
}
