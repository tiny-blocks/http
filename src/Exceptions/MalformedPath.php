<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use RuntimeException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

final class MalformedPath extends RuntimeException implements HttpException
{
    private const string REASON_TEMPLATE = 'Path "%s" is malformed and cannot be composed safely against a base URL.';

    private function __construct(
        private readonly string $url,
        private readonly Method $method,
        private readonly string $reason
    ) {
        parent::__construct($reason);
    }

    public static function fromRequest(Request $request): MalformedPath
    {
        return new MalformedPath(
            url: $request->url,
            method: $request->method,
            reason: sprintf(self::REASON_TEMPLATE, $request->url)
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
