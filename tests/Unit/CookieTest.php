<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\Internal\Server\Exceptions\ConflictingLifetimeAttributes;
use TinyBlocks\Http\Internal\Server\Exceptions\CookieNameIsInvalid;
use TinyBlocks\Http\Internal\Server\Exceptions\CookieValueIsInvalid;
use TinyBlocks\Http\Internal\Server\Exceptions\SameSiteNoneRequiresSecure;
use TinyBlocks\Http\SameSite;

final class CookieTest extends TestCase
{
    public function testCreateWhenNameAndValueGivenThenSerializesNameValuePair(): void
    {
        /** @Given a cookie name and value */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the header contains only the name and value */
        self::assertSame(['Set-Cookie' => ['session=abc']], $actual);
    }

    public function testCreateWhenAllAttributesAppliedThenSerializesInCanonicalOrder(): void
    {
        /** @Given a cookie composed with every supported attribute */
        $cookie = Cookie::create(name: 'refresh_token', value: 'opaque-value')
            ->withMaxAge(seconds: 604800)
            ->withPath(path: '/v1/sessions')
            ->withDomain(domain: 'api.example.com')
            ->secure()
            ->httpOnly()
            ->withSameSite(sameSite: SameSite::STRICT)
            ->partitioned();

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the header includes every attribute in the canonical order */
        $expected = 'refresh_token=opaque-value; Max-Age=604800; Path=/v1/sessions; '
            . 'Domain=api.example.com; Secure; HttpOnly; SameSite=Strict; Partitioned';
        self::assertSame(['Set-Cookie' => [$expected]], $actual);
    }

    public function testExpireWhenInvokedThenEmitsEmptyValueAndMaxAgeZero(): void
    {
        /** @Given a cookie deletion for an existing name */
        /** @And the same path used when the cookie was issued */
        $cookie = Cookie::expire(name: 'refresh_token')->withPath(path: '/v1/sessions');

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the header instructs the browser to discard the cookie */
        self::assertSame(['Set-Cookie' => ['refresh_token=; Max-Age=0; Path=/v1/sessions']], $actual);
    }

    public function testWithValueWhenInvokedThenReturnsNewInstanceAndOriginalIsUntouched(): void
    {
        /** @Given a cookie with an initial value */
        $original = Cookie::create(name: 'session', value: 'initial');

        /** @When a new value is assigned */
        $rotated = $original->withValue(value: 'rotated');

        /** @Then the original instance remains unchanged */
        self::assertSame(['Set-Cookie' => ['session=initial']], $original->toArray());
        /** @And the new instance carries the replaced value */
        self::assertSame(['Set-Cookie' => ['session=rotated']], $rotated->toArray());
    }

    public function testWithExpiresWhenNonUtcDateGivenThenRendersInUtcRfcFormat(): void
    {
        /** @Given an expiration in a non-UTC timezone */
        $cookie = Cookie::create(name: 'session', value: 'abc')->withExpires(
            expires: new DateTimeImmutable('2030-01-15 12:00:00', new DateTimeZone('America/Sao_Paulo'))
        );

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the Expires attribute is converted to UTC and formatted per RFC 7231 */
        self::assertSame(
            ['Set-Cookie' => ['session=abc; Expires=Tue, 15 Jan 2030 15:00:00 GMT']],
            $actual
        );
    }

    public function testSecureWhenInvokedThenReturnsNewInstanceWithFlag(): void
    {
        /** @Given a base cookie without the secure flag */
        $base = Cookie::create(name: 'session', value: 'abc');

        /** @When the secure flag is applied */
        $secured = $base->secure();

        /** @Then the base instance remains unchanged */
        self::assertSame(['Set-Cookie' => ['session=abc']], $base->toArray());
        /** @And the new instance has the secure flag applied */
        self::assertSame(['Set-Cookie' => ['session=abc; Secure']], $secured->toArray());
    }

    public function testToArrayWhenSameSiteNoneWithoutSecureGivenThenThrows(): void
    {
        /** @Given a cookie set to SameSite=None without the Secure flag */
        $cookie = Cookie::create(name: 'session', value: 'abc')->withSameSite(sameSite: SameSite::NONE);

        /** @Then an exception indicating the missing Secure flag is thrown */
        $this->expectException(SameSiteNoneRequiresSecure::class);

        /** @When the header is serialized */
        $cookie->toArray();
    }

    public function testToArrayWhenSameSiteNoneWithSecureGivenThenSerializesBothAttributes(): void
    {
        /** @Given a cookie with SameSite=None combined with Secure */
        $cookie = Cookie::create(name: 'session', value: 'abc')
            ->withSameSite(sameSite: SameSite::NONE)
            ->secure();

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then both attributes are present */
        self::assertSame(['Set-Cookie' => ['session=abc; Secure; SameSite=None']], $actual);
    }

    public function testToArrayWhenBothMaxAgeAndExpiresGivenThenThrows(): void
    {
        /** @Given a cookie with both Max-Age and Expires assigned */
        $cookie = Cookie::create(name: 'session', value: 'abc')
            ->withMaxAge(seconds: 3600)
            ->withExpires(expires: new DateTimeImmutable('2030-01-15 12:00:00 UTC'));

        /** @Then an exception indicating conflicting lifetime attributes is thrown */
        $this->expectException(ConflictingLifetimeAttributes::class);

        /** @When the header is serialized */
        $cookie->toArray();
    }

    public function testCreateWhenEmptyValueGivenThenRendersEmpty(): void
    {
        /** @Given an empty value */
        $cookie = Cookie::create(name: 'session', value: '');

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the value is rendered as empty */
        self::assertSame(['Set-Cookie' => ['session=']], $actual);
    }

    public function testWithValueWhenForbiddenCharacterGivenThenThrows(): void
    {
        /** @Given a valid cookie */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(CookieValueIsInvalid::class);

        /** @When the value is replaced with one containing forbidden characters */
        $cookie->withValue(value: 'has;semicolon');
    }

    public function testExpireWhenInvalidNameGivenThenThrows(): void
    {
        /** @Then an exception indicating the name is invalid is thrown */
        $this->expectException(CookieNameIsInvalid::class);

        /** @When expiring a cookie with an invalid name */
        Cookie::expire(name: 'bad name');
    }

    public function testCreateWhenForbiddenCharacterInValueGivenThenThrows(): void
    {
        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(CookieValueIsInvalid::class);

        /** @When creating a cookie with the invalid value */
        Cookie::create(name: 'session', value: 'abc;def');
    }

    #[DataProvider('invalidNameProvider')]
    public function testCreateWhenInvalidNameGivenThenThrows(string $name): void
    {
        /** @Then an exception indicating the name is invalid is thrown */
        $this->expectException(CookieNameIsInvalid::class);

        /** @When creating a cookie with the invalid name */
        Cookie::create(name: $name, value: 'value');
    }

    #[DataProvider('invalidValueProvider')]
    public function testCreateWhenInvalidValueGivenThenThrows(string $value): void
    {
        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(CookieValueIsInvalid::class);

        /** @When creating a cookie with the invalid value */
        Cookie::create(name: 'session', value: $value);
    }

    public static function invalidNameProvider(): array
    {
        return [
            'Empty name'                  => [''],
            'Name with space'             => ['session id'],
            'Name with semicolon'         => ['session;'],
            'Name with equals'            => ['session=value'],
            'Name with control character' => ["session\x00"],
            'Name with comma'             => ['session,id'],
            'Name with double quote'      => ['session"'],
            'Name with brackets'          => ['session[]']
        ];
    }

    public static function invalidValueProvider(): array
    {
        return [
            'Value with space'             => ['abc def'],
            'Value with tab'               => ["abc\tdef"],
            'Value with semicolon'         => ['abc;def'],
            'Value with comma'             => ['abc,def'],
            'Value with double quote'      => ['abc"def'],
            'Value with backslash'         => ['abc\\def'],
            'Value with control character' => ["abc\x00def"]
        ];
    }
}
