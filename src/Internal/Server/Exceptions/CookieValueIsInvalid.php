<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use InvalidArgumentException;

final class CookieValueIsInvalid extends InvalidArgumentException
{
    private const string REASON_TEMPLATE = 'Cookie value <%s> is invalid. A value must not contain control characters, '
        . 'whitespace, double quotes, commas, semicolons, or backslashes. Encode the value '
        . '(e.g., URL-encode or Base64) before passing it.';

    public function __construct(string $value)
    {
        $template = CookieValueIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $value));
    }
}
