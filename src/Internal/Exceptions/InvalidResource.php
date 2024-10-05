<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use RuntimeException;

final class InvalidResource extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'The provided value is not a valid resource.');
    }
}
