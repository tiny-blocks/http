<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use RuntimeException;

final class NonReadableStream extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'Stream is not readable.');
    }
}
