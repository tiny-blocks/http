<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use InvalidArgumentException;

final class CookiePathIsInvalid extends InvalidArgumentException
{
    private const string REASON_TEMPLATE = 'Cookie path <%s> is invalid. A path must not contain control characters, '
        . 'semicolons, or commas.';

    public function __construct(string $path)
    {
        $template = CookiePathIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $path));
    }
}
