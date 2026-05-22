<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use Throwable;

/**
 * Common contract implemented by every exception raised by this library.
 *
 * Allows a single catch clause to handle any failure originating from the HTTP layer, regardless
 * of whether it stems from configuration, request resolution, transport dispatch, or library
 * invariant violations.
 */
interface HttpException extends Throwable
{
}
