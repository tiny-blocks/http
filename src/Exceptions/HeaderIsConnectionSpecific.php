<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a response is given a connection-specific header.
 *
 * <code>Connection</code>, <code>Keep-Alive</code>, <code>Proxy-Connection</code>,
 * <code>Transfer-Encoding</code> and <code>Upgrade</code> describe the connection that carries a
 * message, not the message, and HTTP/2 (RFC 9113 section 8.2.2) and HTTP/3 (RFC 9114 section 4.2)
 * treat a message carrying one as malformed. <code>TE</code> is admitted only with the value
 * <code>trailers</code>.
 */
final class HeaderIsConnectionSpecific extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Header <%s> is connection-specific and must not be set on a response. '
        . 'RFC 9113 section 8.2.2 and RFC 9114 section 4.2 forbid it, and TE admits only the value trailers.';

    private function __construct(string $name)
    {
        $template = HeaderIsConnectionSpecific::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $name));
    }

    /**
     * Creates a HeaderIsConnectionSpecific signaling that the given header must not be set on a response.
     *
     * @param string $name The offending header name.
     * @return HeaderIsConnectionSpecific The composed exception describing the connection-specific header.
     */
    public static function for(string $name): HeaderIsConnectionSpecific
    {
        return new HeaderIsConnectionSpecific(name: $name);
    }
}
