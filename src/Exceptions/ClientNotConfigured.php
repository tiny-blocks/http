<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;

/**
 * Raised when <code>RetryingClientBuilder::build()</code> is called without a PSR-18 client configured.
 */
final class ClientNotConfigured extends LogicException implements HttpException
{
    private const string REASON = 'A client must be provided to build the RetryingClient.';

    private function __construct()
    {
        parent::__construct(message: ClientNotConfigured::REASON);
    }

    /**
     * Creates a ClientNotConfigured signaling that the PSR-18 client is missing.
     *
     * @return ClientNotConfigured The composed exception describing the missing-client state.
     */
    public static function create(): ClientNotConfigured
    {
        return new ClientNotConfigured();
    }
}
