<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a response is given a protocol version outside the HTTP versions it supports.
 *
 * A response speaks HTTP/1.0, HTTP/1.1, HTTP/2 or HTTP/3, written as <code>1.0</code>,
 * <code>1.1</code>, <code>2</code> and <code>3</code>. The forms <code>2.0</code> and
 * <code>3.0</code> are accepted and stored as <code>2</code> and <code>3</code>. Anything else is
 * rejected, because an emitter writes the version into the status line.
 */
final class ProtocolVersionIsUnsupported extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Protocol version <%s> is not supported. Supported versions are 1.0, '
        . '1.1, 2 and 3.';

    private function __construct(string $version)
    {
        $template = ProtocolVersionIsUnsupported::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $version));
    }

    /**
     * Creates a ProtocolVersionIsUnsupported signaling that the given version is not a supported HTTP version.
     *
     * @param string $version The offending protocol version.
     * @return ProtocolVersionIsUnsupported The composed exception describing the unsupported version.
     */
    public static function for(string $version): ProtocolVersionIsUnsupported
    {
        return new ProtocolVersionIsUnsupported(version: $version);
    }
}
