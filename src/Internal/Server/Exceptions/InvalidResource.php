<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use RuntimeException;

final class InvalidResource extends RuntimeException
{
    private const string REASON = 'The provided value is not a valid resource.';

    public function __construct()
    {
        parent::__construct(self::REASON);
    }
}
