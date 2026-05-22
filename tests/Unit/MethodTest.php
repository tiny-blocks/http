<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Method;

final class MethodTest extends TestCase
{
    #[DataProvider('safeMethodCases')]
    public function testIsSafeWhenSafeMethodGivenThenReturnsTrue(Method $method): void
    {
        /** @Given a safe HTTP method */

        /** @When checking isSafe */
        $actual = $method->isSafe();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    #[DataProvider('unsafeMethodCases')]
    public function testIsSafeWhenUnsafeMethodGivenThenReturnsFalse(Method $method): void
    {
        /** @Given an unsafe HTTP method */

        /** @When checking isSafe */
        $actual = $method->isSafe();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    #[DataProvider('idempotentMethodCases')]
    public function testIsIdempotentWhenIdempotentMethodGivenThenReturnsTrue(Method $method): void
    {
        /** @Given an idempotent HTTP method */

        /** @When checking isIdempotent */
        $actual = $method->isIdempotent();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    #[DataProvider('nonIdempotentMethodCases')]
    public function testIsIdempotentWhenNonIdempotentMethodGivenThenReturnsFalse(Method $method): void
    {
        /** @Given a non-idempotent HTTP method */

        /** @When checking isIdempotent */
        $actual = $method->isIdempotent();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public static function safeMethodCases(): array
    {
        return [
            'GET'     => ['method' => Method::GET],
            'HEAD'    => ['method' => Method::HEAD],
            'OPTIONS' => ['method' => Method::OPTIONS],
            'TRACE'   => ['method' => Method::TRACE]
        ];
    }

    public static function unsafeMethodCases(): array
    {
        return [
            'POST'    => ['method' => Method::POST],
            'PUT'     => ['method' => Method::PUT],
            'PATCH'   => ['method' => Method::PATCH],
            'DELETE'  => ['method' => Method::DELETE],
            'CONNECT' => ['method' => Method::CONNECT]
        ];
    }

    public static function idempotentMethodCases(): array
    {
        return [
            'GET'     => ['method' => Method::GET],
            'PUT'     => ['method' => Method::PUT],
            'HEAD'    => ['method' => Method::HEAD],
            'TRACE'   => ['method' => Method::TRACE],
            'DELETE'  => ['method' => Method::DELETE],
            'OPTIONS' => ['method' => Method::OPTIONS]
        ];
    }

    public static function nonIdempotentMethodCases(): array
    {
        return [
            'POST'    => ['method' => Method::POST],
            'PATCH'   => ['method' => Method::PATCH],
            'CONNECT' => ['method' => Method::CONNECT]
        ];
    }
}
