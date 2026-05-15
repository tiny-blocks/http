<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use RuntimeException;

final class NonSeekableStream extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'Stream is not seekable.');
    }
}
