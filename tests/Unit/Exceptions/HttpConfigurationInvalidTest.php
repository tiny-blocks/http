<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use LogicException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;

final class HttpConfigurationInvalidTest extends TestCase
{
    public function testMissingTransportCreatesExceptionWithCorrectMessage(): void
    {
        /** @When creating the exception for missing transport */
        $exception = HttpConfigurationInvalid::missingTransport();

        /** @Then the message describes the missing transport */
        self::assertSame('Transport is required to build Http.', $exception->getMessage());
        self::assertInstanceOf(LogicException::class, $exception);
    }

    public function testMissingBaseUrlCreatesExceptionWithCorrectMessage(): void
    {
        /** @When creating the exception for missing base URL */
        $exception = HttpConfigurationInvalid::missingBaseUrl();

        /** @Then the message describes the missing base URL */
        self::assertSame('Base URL is required to build Http.', $exception->getMessage());
        self::assertInstanceOf(LogicException::class, $exception);
    }
}
