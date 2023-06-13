<?php

namespace TinyBlocks\Http\Internal\Exceptions;

use RuntimeException;

final class MissingResourceStream extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'No resource available.');
    }
}
