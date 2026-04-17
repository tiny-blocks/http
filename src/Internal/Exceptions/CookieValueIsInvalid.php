<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use InvalidArgumentException;

final class CookieValueIsInvalid extends InvalidArgumentException
{
    public function __construct(string $value)
    {
        $template = sprintf(
            '%s%s%s',
            'Cookie value <%s> is invalid. A value must not contain control characters, whitespace, ',
            'double quotes, commas, semicolons, or backslashes. Encode the value (e.g., URL-encode or ',
            'Base64) before passing it.'
        );

        parent::__construct(sprintf($template, $value));
    }
}
