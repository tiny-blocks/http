<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;
use TinyBlocks\Http\Exceptions\HttpException;

final class HttpConfigurationInvalidTest extends TestCase
{
    public function testMissingTransportWhenInvokedThenMessageDescribesMissingTransport(): void
    {
        /** @When creating the exception for missing transport */
        $exception = HttpConfigurationInvalid::missingTransport();

        /** @Then the message describes the missing transport and is recognized as HttpException */
        self::assertSame('Transport is required to build Http.', $exception->getMessage());
        self::assertInstanceOf(HttpException::class, $exception);
    }

    public function testMissingBaseUrlWhenInvokedThenMessageDescribesMissingBaseUrl(): void
    {
        /** @When creating the exception for missing base URL */
        $exception = HttpConfigurationInvalid::missingBaseUrl();

        /** @Then the message describes the missing base URL and is recognized as HttpException */
        self::assertSame('Base URL is required to build Http.', $exception->getMessage());
        self::assertInstanceOf(HttpException::class, $exception);
    }
}
