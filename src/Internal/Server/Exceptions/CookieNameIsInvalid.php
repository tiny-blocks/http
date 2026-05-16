<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use InvalidArgumentException;

final class CookieNameIsInvalid extends InvalidArgumentException
{
    private const string REASON_TEMPLATE = 'Cookie name <%s> is invalid. A name must not be empty and must not contain '
        . 'control characters, whitespace, or any of the following separators: ( ) < > @ , ; : \\ " / [ ] ? = { }.';

    public function __construct(string $name)
    {
        $template = CookieNameIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $name));
    }
}
