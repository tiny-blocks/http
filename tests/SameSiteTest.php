<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\SameSite;

final class SameSiteTest extends TestCase
{
    #[DataProvider('sameSiteValueProvider')]
    public function testBackedValueMatchesHeaderSpelling(SameSite $sameSite, string $expected): void
    {
        /** @Given a SameSite enum case */
        /** @When the backed value is read */
        $actual = $sameSite->value;

        /** @Then the value should match the casing expected by the Set-Cookie header */
        self::assertSame($expected, $actual);
    }

    public static function sameSiteValueProvider(): array
    {
        return [
            'Lax strategy'    => [SameSite::LAX, 'Lax'],
            'None strategy'   => [SameSite::NONE, 'None'],
            'Strict strategy' => [SameSite::STRICT, 'Strict'],
        ];
    }
}
