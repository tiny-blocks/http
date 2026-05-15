<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Internal\Client;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Client\Cursor;

final class CursorTest extends TestCase
{
    public function testFirstAdvanceReturnsZero(): void
    {
        /** @Given a new cursor */
        $cursor = new Cursor();

        /** @When advancing for the first time */
        $position = $cursor->advance();

        /** @Then the position is 0 */
        self::assertSame(0, $position);
    }

    public function testSecondAdvanceReturnsOne(): void
    {
        /** @Given a cursor that has been advanced once */
        $cursor = new Cursor();
        $cursor->advance();

        /** @When advancing a second time */
        $position = $cursor->advance();

        /** @Then the position is 1 */
        self::assertSame(1, $position);
    }

    public function testThirdAdvanceReturnsTwo(): void
    {
        /** @Given a cursor that has been advanced twice */
        $cursor = new Cursor();
        $cursor->advance();
        $cursor->advance();

        /** @When advancing a third time */
        $position = $cursor->advance();

        /** @Then the position is 2 */
        self::assertSame(2, $position);
    }
}
