<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a header value contains characters that would enable header injection.
 *
 * Any value containing a control character other than horizontal tab is rejected. This
 * covers carriage return, line feed, and other ASCII control codes that allow an attacker to
 * inject additional response headers.
 */
final class HeaderValueIsInvalid extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Header value <%s> is invalid. Values must not contain control characters '
        . 'other than horizontal tab (0x09).';

    private function __construct(string $value)
    {
        $template = HeaderValueIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $value));
    }

    /**
     * Creates a HeaderValueIsInvalid signaling that the given value contains a forbidden control character.
     *
     * @param string $value The offending header value.
     * @return HeaderValueIsInvalid The composed exception describing the invalid header value.
     */
    public static function for(string $value): HeaderValueIsInvalid
    {
        return new HeaderValueIsInvalid(value: $value);
    }
}
