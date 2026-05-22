<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\UserAgentProductIsEmpty;
use TinyBlocks\Http\Exceptions\UserAgentValueIsInvalid;
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

    public function testFromWhenEmptyVersionGivenThenEquivalentToProductOnly(): void
    {
        /** @Given a product token with an explicitly empty version */
        $userAgent = UserAgent::from(product: 'MyApp', version: '');

        /** @When reading the header array */
        $header = $userAgent->toArray();

        /** @Then the header carries only the product token */
        self::assertSame(['User-Agent' => 'MyApp'], $header);
    }

    public function testFromWhenValidProductOnlyGivenThenNoExceptionIsThrown(): void
    {
        /** @When constructing with a valid product token */
        $userAgent = UserAgent::from(product: 'ValidApp');

        /** @Then the header is rendered without error */
        self::assertSame(['User-Agent' => 'ValidApp'], $userAgent->toArray());
    }

    public function testFromWhenEmptyProductGivenThenThrowsUserAgentProductIsEmpty(): void
    {
        /** @Then an exception is thrown */
        $this->expectException(UserAgentProductIsEmpty::class);
        $this->expectExceptionMessage('User-Agent product must not be empty.');

        /** @When constructing with an empty product token */
        UserAgent::from(product: '');
    }

    public function testFromWhenValidProductAndVersionGivenThenNoExceptionIsThrown(): void
    {
        /** @When constructing with a valid product and version */
        $userAgent = UserAgent::from(product: 'ValidApp', version: '2.0');

        /** @Then the header is rendered without error */
        self::assertSame(['User-Agent' => 'ValidApp/2.0'], $userAgent->toArray());
    }

    public function testFromWhenProductWithLfGivenThenThrowsUserAgentValueIsInvalid(): void
    {
        /** @Then an exception indicating the product token is invalid is thrown */
        $this->expectException(UserAgentValueIsInvalid::class);

        /** @When constructing with a product containing a line feed */
        UserAgent::from(product: "My\nApp");
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

    public function testFromWhenProductWithSlashGivenThenThrowsUserAgentValueIsInvalid(): void
    {
        /** @Then an exception indicating the product token is invalid is thrown */
        $this->expectException(UserAgentValueIsInvalid::class);

        /** @When constructing with a product containing a forward slash */
        UserAgent::from(product: 'My/App');
    }

    public function testFromWhenProductWithControlCharGivenThenThrowsUserAgentValueIsInvalid(): void
    {
        /** @Then an exception indicating the product token is invalid is thrown */
        $this->expectException(UserAgentValueIsInvalid::class);
        $this->expectExceptionMessage('is invalid');

        /** @When constructing with a product containing a control character */
        UserAgent::from(product: "MyApp\x00");
    }

    public function testFromWhenVersionWithControlCharGivenThenThrowsUserAgentValueIsInvalid(): void
    {
        /** @Given a valid product token */

        /** @Then an exception indicating the version token is invalid is thrown */
        $this->expectException(UserAgentValueIsInvalid::class);

        /** @When constructing with a version containing a control character */
        UserAgent::from(product: 'MyApp', version: "1\x002");
    }
}
