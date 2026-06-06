<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a link URI cannot serve as an RFC 8288 link target.
 *
 * A link URI must be non-empty and free of carriage return, line feed, and the angle brackets
 * that delimit the link target. Such characters would break the Link header field.
 */
final class LinkUriIsInvalid extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Link URI <%s> is invalid. A link URI must be non-empty and free of CR, '
        . 'LF, and angle brackets that would break the RFC 8288 link target.';

    private function __construct(string $uri)
    {
        $template = LinkUriIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $uri));
    }

    /**
     * Creates a LinkUriIsInvalid signaling that the given link URI cannot serve as an RFC 8288 link target.
     *
     * @param string $uri The offending link URI.
     * @return LinkUriIsInvalid The composed exception describing the invalid link URI.
     */
    public static function for(string $uri): LinkUriIsInvalid
    {
        return new LinkUriIsInvalid(uri: $uri);
    }
}
