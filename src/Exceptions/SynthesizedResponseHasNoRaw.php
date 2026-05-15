<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;
use TinyBlocks\Http\Method;

final class SynthesizedResponseHasNoRaw extends LogicException implements HttpException
{
    private const string REASON = 'Response was synthesized via Response::with(...) and has no underlying PSR-7 raw response.';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason
    ) {
        parent::__construct($reason);
    }

    public static function create(): SynthesizedResponseHasNoRaw
    {
        return new SynthesizedResponseHasNoRaw(url: '', method: Method::GET, reason: self::REASON);
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
