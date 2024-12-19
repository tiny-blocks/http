<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use BadMethodCallException;

final class BadMethodCall extends BadMethodCallException
{
    public function __construct(private readonly string $method)
    {
        $template = 'Method <%s> cannot be used.';

        parent::__construct(message: sprintf($template, $this->method));
    }
}
