<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use LogicException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\NoMoreResponses;

final class NoMoreResponsesTest extends TestCase
{
    public function testAtIndexCreatesExceptionWithCorrectMessage(): void
    {
        /** @When creating the exception at index 3 */
        $exception = NoMoreResponses::atIndex(index: 3);

        /** @Then the message references the index */
        self::assertStringContainsString('3', $exception->getMessage());
        self::assertInstanceOf(LogicException::class, $exception);
    }

    public function testAtIndexZeroCreatesExceptionWithCorrectMessage(): void
    {
        /** @When creating the exception at index 0 */
        $exception = NoMoreResponses::atIndex(index: 0);

        /** @Then the message references index 0 */
        self::assertStringContainsString('0', $exception->getMessage());
        self::assertStringContainsString('InMemoryTransport', $exception->getMessage());
    }
}
