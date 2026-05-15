<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Throwable;
use TinyBlocks\Http\Method;

interface HttpException extends Throwable
{
    /**
     * The fully composed URL the failed request targeted.
     *
     * @return string The absolute URL.
     *
     * @complexity O(1) time and space.
     */
    public function url(): string;

    /**
     * The transport-level or domain-level reason for the failure.
     *
     * @return string The human-readable reason already formatted into the message.
     *
     * @complexity O(1) time and space.
     */
    public function reason(): string;

    /**
     * The HTTP method used in the failed request.
     *
     * @return Method The verb of the failed request.
     *
     * @complexity O(1) time and space.
     */
    public function method(): Method;
}
