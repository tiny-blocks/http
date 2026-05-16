<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Closure;
use RuntimeException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;

final readonly class FailingTransport implements Transport
{
    private function __construct(private Closure $factory)
    {
    }

    public static function raisingNetworkFailure(string $reason, RuntimeException $cause): FailingTransport
    {
        $factory = static fn(Request $request): HttpException => HttpNetworkFailed::from(
            url: $request->url(),
            method: $request->method(),
            reason: $reason,
            previous: $cause
        );

        return new FailingTransport(factory: $factory);
    }

    public static function raisingRequestInvalid(string $reason, RuntimeException $cause): FailingTransport
    {
        $factory = static fn(Request $request): HttpException => HttpRequestInvalid::from(
            url: $request->url(),
            method: $request->method(),
            reason: $reason,
            previous: $cause
        );

        return new FailingTransport(factory: $factory);
    }

    public static function raisingRequestFailure(string $reason, RuntimeException $cause): FailingTransport
    {
        $factory = static fn(Request $request): HttpException => HttpRequestFailed::from(
            url: $request->url(),
            method: $request->method(),
            reason: $reason,
            previous: $cause
        );

        return new FailingTransport(factory: $factory);
    }

    public function send(Request $request): Response
    {
        throw ($this->factory)($request);
    }
}
