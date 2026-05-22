<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;

/**
 * Raised when <code>HttpBuilder::build()</code> is called without all required dependencies configured
 * (base URL and Transport).
 */
final class HttpConfigurationInvalid extends LogicException implements HttpException
{
    private const string MISSING_BASE_URL_REASON = 'Base URL is required to build Http.';
    private const string MISSING_TRANSPORT_REASON = 'Transport is required to build Http.';

    private function __construct(string $reason)
    {
        parent::__construct(message: $reason);
    }

    /**
     * Creates an HttpConfigurationInvalid signaling that the base URL is missing.
     *
     * @return HttpConfigurationInvalid A configuration error reporting the missing base URL.
     */
    public static function missingBaseUrl(): HttpConfigurationInvalid
    {
        return new HttpConfigurationInvalid(reason: HttpConfigurationInvalid::MISSING_BASE_URL_REASON);
    }

    /**
     * Creates an HttpConfigurationInvalid signaling that the transport is missing.
     *
     * @return HttpConfigurationInvalid A configuration error reporting the missing transport.
     */
    public static function missingTransport(): HttpConfigurationInvalid
    {
        return new HttpConfigurationInvalid(reason: HttpConfigurationInvalid::MISSING_TRANSPORT_REASON);
    }
}
