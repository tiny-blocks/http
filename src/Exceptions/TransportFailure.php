<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use TinyBlocks\Http\Method;

interface TransportFailure extends HttpException
{
    /**
     * Returns the URL the failed request targeted.
     *
     * @return string The fully composed URL associated with the failure.
     */
    public function url(): string;

    /**
     * Returns the HTTP method of the failed request.
     *
     * @return Method The HTTP method associated with the failure.
     */
    public function method(): Method;

    /**
     * Returns the transport-level reason for the failure.
     *
     * @return string The reason text associated with the failure.
     */
    public function reason(): string;
}
