<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\NoMoreResponses;

final class NoMoreResponsesTest extends TestCase
{
    public function testAtIndexWhenIndexThreeGivenThenMessageReferencesIndex(): void
    {
        /** @When creating the exception at index 3 */
        $exception = NoMoreResponses::atIndex(index: 3);

        /** @Then the message references the index and the exception is an HttpException */
        self::assertStringContainsString('3', $exception->getMessage());
        self::assertInstanceOf(HttpException::class, $exception);
    }

    public function testAtIndexWhenIndexZeroGivenThenMessageReferencesIndexAndTransport(): void
    {
        /** @When creating the exception at index 0 */
        $exception = NoMoreResponses::atIndex(index: 0);

        /** @Then the message references index 0 and the InMemoryTransport */
        self::assertStringContainsString('0', $exception->getMessage());
        self::assertStringContainsString('InMemoryTransport', $exception->getMessage());
    }
}
