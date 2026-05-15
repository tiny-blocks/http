<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;
use TinyBlocks\Http\Method;

final class NoMoreResponses extends LogicException implements HttpException
{
    private const string REASON_TEMPLATE = 'InMemoryTransport has no response queued at index %d.';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason
    ) {
        parent::__construct($reason);
    }

    public static function atIndex(int $index): NoMoreResponses
    {
        return new NoMoreResponses(
            url: '',
            method: Method::GET,
            reason: sprintf(self::REASON_TEMPLATE, $index)
        );
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
