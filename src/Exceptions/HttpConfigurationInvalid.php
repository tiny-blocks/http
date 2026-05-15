<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;
use TinyBlocks\Http\Method;

final class HttpConfigurationInvalid extends LogicException implements HttpException
{
    private const string MISSING_BASE_URL_REASON = 'Base URL is required to build Http.';
    private const string MISSING_TRANSPORT_REASON = 'Transport is required to build Http.';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason
    ) {
        parent::__construct($reason);
    }

    public static function missingBaseUrl(): HttpConfigurationInvalid
    {
        return new HttpConfigurationInvalid(url: '', method: Method::GET, reason: self::MISSING_BASE_URL_REASON);
    }

    public static function missingTransport(): HttpConfigurationInvalid
    {
        return new HttpConfigurationInvalid(url: '', method: Method::GET, reason: self::MISSING_TRANSPORT_REASON);
    }

    public function url(): string
    {
        return $this->url;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function method(): Method
    {
        return $this->method;
    }
}
