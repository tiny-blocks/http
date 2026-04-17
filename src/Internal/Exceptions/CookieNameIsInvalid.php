<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use InvalidArgumentException;

final class CookieNameIsInvalid extends InvalidArgumentException
{
    public function __construct(string $name)
    {
        $template = sprintf(
            '%s%s',
            'Cookie name <%s> is invalid. A name must not be empty and must not contain control ',
            'characters, whitespace, or any of the following separators: ( ) < > @ , ; : \\ " / [ ] ? = { }.'
        );

        parent::__construct(sprintf($template, $name));
    }
}
