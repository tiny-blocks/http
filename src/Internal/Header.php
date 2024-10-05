<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

interface Header
{
    /**
     * Get the key of the header.
     *
     * @return string The key of the header.
     */
    public function key(): string;

    /**
     * Get the value of the header.
     *
     * @return string The value of the header.
     */
    public function value(): string;
}
