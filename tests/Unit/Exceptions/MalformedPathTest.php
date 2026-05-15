<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\MalformedPath;
use TinyBlocks\Http\Method;

final class MalformedPathTest extends TestCase
{
    public function testFromRequestWhenMalformedPathGivenThenExposesPath(): void
    {
        /** @Given a request with a malformed path */
        $request = Request::create(url: '//evil.example.com/attack', method: Method::GET);

        /** @When constructing from the request */
        $exception = MalformedPath::fromRequest(request: $request);

        /** @Then the exception exposes the path via url() */
        self::assertSame('//evil.example.com/attack', $exception->url());
        self::assertSame(Method::GET, $exception->method());
        self::assertStringContainsString('//evil.example.com/attack', $exception->reason());
    }

    public function testFromRequestWhenAnyMalformedPathGivenThenImplementsHttpException(): void
    {
        /** @Given a MalformedPath exception built from a scheme-containing path */
        $exception = MalformedPath::fromRequest(
            request: Request::create(url: 'javascript:alert(1)')
        );

        /** @Then it is catchable as HttpException */
        self::assertInstanceOf(HttpException::class, $exception);
    }

    public function testFromRequestWhenSchemePathGivenThenReasonDescribesPath(): void
    {
        /** @Given a request with a scheme-containing path */
        $request = Request::create(url: 'https://attacker.com/steal');

        /** @When constructing from the request */
        $exception = MalformedPath::fromRequest(request: $request);

        /** @Then the reason message references the malformed path */
        self::assertStringContainsString('https://attacker.com/steal', $exception->reason());
        self::assertStringContainsString('malformed', $exception->reason());
    }
}
