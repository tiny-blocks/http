<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use RuntimeException;

final class NonWritableStream extends RuntimeException
{
    private const string REASON = 'Stream is not writable.';

    public function __construct()
    {
        parent::__construct(self::REASON);
    }
}
