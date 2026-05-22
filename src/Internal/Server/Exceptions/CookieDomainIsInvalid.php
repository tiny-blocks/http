<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use InvalidArgumentException;

final class CookieDomainIsInvalid extends InvalidArgumentException
{
    private const string REASON_TEMPLATE = 'Cookie domain <%s> is invalid. A domain must not contain control '
        . 'characters, whitespace, semicolons, commas, double quotes, or backslashes.';

    public function __construct(string $domain)
    {
        $template = CookieDomainIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $domain));
    }
}
