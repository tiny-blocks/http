<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\UserAgent;

final class UserAgentTest extends TestCase
{
    public function testFromWhenProductOnlyGivenThenRendersProductToken(): void
    {
        /** @Given a product token without a version */
        $userAgent = UserAgent::from(product: 'MyApp');

        /** @When reading the header array */
        $header = $userAgent->toArray();

        /** @Then the header contains only the product token */
        self::assertSame(['User-Agent' => 'MyApp'], $header);
    }

    public function testFromWhenEmptyVersionGivenThenEquivalentToProductOnly(): void
    {
        /** @Given a product token with an explicitly empty version */
        $userAgent = UserAgent::from(product: 'MyApp', version: '');

        /** @When reading the header array */
        $header = $userAgent->toArray();

        /** @Then the header carries only the product token */
        self::assertSame(['User-Agent' => 'MyApp'], $header);
    }

    public function testFromWhenProductAndVersionGivenThenRendersProductSlashVersion(): void
    {
        /** @Given a product token and a version */
        $userAgent = UserAgent::from(product: 'MyApp', version: '1.2.3');

        /** @When reading the header array */
        $header = $userAgent->toArray();

        /** @Then the header contains the product and version combined */
        self::assertSame(['User-Agent' => 'MyApp/1.2.3'], $header);
    }

    public function testToArrayWhenInvokedRepeatedlyThenReturnsSameValue(): void
    {
        /** @Given a UserAgent value object */
        $userAgent = UserAgent::from(product: 'MyApp', version: '1.2.3');

        /** @When calling toArray multiple times */
        $first = $userAgent->toArray();
        $second = $userAgent->toArray();

        /** @Then both calls return identical arrays */
        self::assertSame($first, $second);
    }

    public function testFromWhenEmptyProductGivenThenThrowsInvalidArgumentException(): void
    {
        /** @Then an exception is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User-Agent product must not be empty.');

        /** @When constructing with an empty product token */
        UserAgent::from(product: '');
    }
}
