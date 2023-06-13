<?php

namespace TinyBlocks\Http\Internal\Exceptions;

use RuntimeException;

final class NonWritableStream extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'Stream is not writable.');
    }
}
