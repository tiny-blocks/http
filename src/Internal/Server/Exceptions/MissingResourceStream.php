<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use RuntimeException;

final class MissingResourceStream extends RuntimeException
{
    private const string REASON = 'No resource available.';

    public function __construct()
    {
        parent::__construct(self::REASON);
    }
}
