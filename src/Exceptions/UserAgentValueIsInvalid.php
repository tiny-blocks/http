<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a product token or version token passed to <code>UserAgent::from()</code> contains
 * characters that would corrupt the rendered header line.
 *
 * The product token must not contain control characters or a forward slash (the product-version
 * separator in the rendered header). The version token must not contain control characters.
 */
final class UserAgentValueIsInvalid extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'User-Agent token <%s> is invalid. Tokens must not contain control '
        . 'characters (0x00-0x1F, 0x7F). The product token also must not contain a forward slash.';

    private function __construct(string $value)
    {
        $template = UserAgentValueIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $value));
    }

    /**
     * Creates a UserAgentValueIsInvalid signaling that the given token is invalid.
     *
     * @param string $value The offending product or version token.
     * @return UserAgentValueIsInvalid The composed exception describing the invalid token.
     */
    public static function for(string $value): UserAgentValueIsInvalid
    {
        return new UserAgentValueIsInvalid(value: $value);
    }
}
