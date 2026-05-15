<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;

final class HttpConfigurationInvalid extends LogicException
{
    private const string MISSING_BASE_URL_REASON = 'Base URL is required to build Http.';
    private const string MISSING_TRANSPORT_REASON = 'Transport is required to build Http.';

    public static function missingBaseUrl(): HttpConfigurationInvalid
    {
        return new self(self::MISSING_BASE_URL_REASON);
    }

    public static function missingTransport(): HttpConfigurationInvalid
    {
        return new self(self::MISSING_TRANSPORT_REASON);
    }
}
