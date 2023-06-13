<?php

namespace TinyBlocks\Http\Internal\Exceptions;

use RuntimeException;

final class NonSeekableStream extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'Stream is not seekable.');
    }
}
