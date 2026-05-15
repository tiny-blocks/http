<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Throwable;
use TinyBlocks\Http\Method;

interface HttpException extends Throwable
{
    /**
     * @return string The URL of the failed request.
     */
    public function url(): string;

    /**
     * @return string The reason for the failure.
     */
    public function reason(): string;

    /**
     * @return Method The method of the failed request.
     */
    public function method(): Method;
}
