<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a header name fails RFC 7230 token validation.
 *
 * Header names must be non-empty and composed exclusively of printable US-ASCII characters
 * that are not delimiters. Names containing control characters, whitespace, colons, or other
 * separator characters are rejected.
 */
final class HeaderNameIsInvalid extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Header name <%s> is invalid. Names must match the RFC 7230 token '
        . 'production: non-empty, no control characters, no whitespace, no separator characters.';

    private function __construct(string $name)
    {
        $template = HeaderNameIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $name));
    }

    /**
     * Creates a HeaderNameIsInvalid signaling that the given header name violates RFC 7230 token rules.
     *
     * @param string $name The offending header name.
     * @return HeaderNameIsInvalid The composed exception describing the invalid header name.
     */
    public static function for(string $name): HeaderNameIsInvalid
    {
        return new HeaderNameIsInvalid(name: $name);
    }
}
