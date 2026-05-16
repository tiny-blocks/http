<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use RuntimeException;

final class NonSeekableStream extends RuntimeException
{
    private const string REASON = 'Stream is not seekable.';

    public function __construct()
    {
        parent::__construct(message: NonSeekableStream::REASON);
    }
}
