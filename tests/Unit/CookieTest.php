<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\SameSite;

final class CookieTest extends TestCase
{
    #[DataProvider('invalidNameProvider')]
    public function testCreateWhenInvalidNameGivenThenThrows(string $name): void
    {
        /**
         * @Given an invalid cookie name
         * @When  Cookie::create is called with that name
         * @Then  it throws CookieNameIsInvalid
         */
        $this->expectException(InvalidArgumentException::class);

        Cookie::create(name: $name, value: 'value');
    }

    public function testExpireWhenInvalidNameGivenThenThrows(): void
    {
        /** @Then an exception indicating the name is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie name <bad name> is invalid');

        /** @When expiring a cookie with an invalid name */
        Cookie::expire(name: 'bad name');
    }

    #[DataProvider('invalidValueProvider')]
    public function testCreateWhenInvalidValueGivenThenThrows(string $value): void
    {
        /**
         * @Given an invalid cookie value
         * @When  Cookie::create is called with that value
         * @Then  it throws CookieValueIsInvalid
         */
        $this->expectException(InvalidArgumentException::class);

        Cookie::create(name: 'session', value: $value);
    }

    #[DataProvider('invalidPathProvider')]
    public function testWithPathWhenInvalidPathGivenThenThrows(string $path): void
    {
        /** @Given a valid cookie */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @Then an exception indicating the path is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is invalid');

        /** @When setting the path to an invalid value */
        $cookie->withPath(path: $path);
    }

    public function testSecureWhenInvokedThenLeavesBaseUntouched(): void
    {
        /** @Given a base cookie without the secure flag */
        $base = Cookie::create(name: 'session', value: 'abc');

        /** @When the secure flag is applied */
        $base->secure();

        /** @Then the base instance remains unchanged */
        self::assertSame(['Set-Cookie' => ['session=abc']], $base->toArray());
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

    #[DataProvider('invalidDomainProvider')]
    public function testWithDomainWhenInvalidDomainGivenThenThrows(string $domain): void
    {
        /** @Given a valid cookie */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @Then an exception indicating the domain is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is invalid');

        /** @When setting the domain to an invalid value */
        $cookie->withDomain(domain: $domain);
    }

    public function testWithValueWhenForbiddenCharacterGivenThenThrows(): void
    {
        /** @Given a valid cookie */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie value <has;semicolon> is invalid');

        /** @When the value is replaced with one containing forbidden characters */
        $cookie->withValue(value: 'has;semicolon');
    }

    public function testSecureWhenInvokedThenReturnsNewInstanceWithFlag(): void
    {
        /** @Given a base cookie without the secure flag */
        $base = Cookie::create(name: 'session', value: 'abc');

        /** @When the secure flag is applied */
        $secured = $base->secure();

        /** @Then the new instance has the secure flag applied */
        self::assertSame(['Set-Cookie' => ['session=abc; Secure']], $secured->toArray());
    }

    public function testWithValueWhenInvokedThenLeavesOriginalUntouched(): void
    {
        /** @Given a cookie with an initial value */
        $original = Cookie::create(name: 'session', value: 'initial');

        /** @When a new value is assigned */
        $original->withValue(value: 'rotated');

        /** @Then the original instance remains unchanged */
        self::assertSame(['Set-Cookie' => ['session=initial']], $original->toArray());
    }

    public function testCreateWhenForbiddenCharacterInValueGivenThenThrows(): void
    {
        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When creating a cookie with the invalid value */
        Cookie::create(name: 'session', value: 'abc;def');
    }

    public function testCreateWhenNameAndValueGivenThenSerializesNameValuePair(): void
    {
        /** @Given a cookie name and value */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the header contains only the name and value */
        self::assertSame(['Set-Cookie' => ['session=abc']], $actual);
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

    public function testWithSameSiteWhenNoneGivenThenAutomaticallyEnablesSecure(): void
    {
        /** @Given a cookie without the Secure flag */
        $cookie = Cookie::create(name: 'session', value: 'abc');

        /** @When SameSite=None is set */
        $updated = $cookie->withSameSite(sameSite: SameSite::NONE);

        /** @Then both Secure and SameSite=None are present */
        self::assertSame(['Set-Cookie' => ['session=abc; Secure; SameSite=None']], $updated->toArray());
    }

    public function testWithValueWhenInvokedThenReturnsNewInstanceWithReplacedValue(): void
    {
        /** @Given a cookie with an initial value */
        $original = Cookie::create(name: 'session', value: 'initial');

        /** @When a new value is assigned */
        $rotated = $original->withValue(value: 'rotated');

        /** @Then the new instance carries the replaced value */
        self::assertSame(['Set-Cookie' => ['session=rotated']], $rotated->toArray());
    }

    public function testCreateWhenAllAttributesAppliedThenSerializesInCanonicalOrder(): void
    {
        /** @Given a cookie composed with every supported attribute */
        $cookie = Cookie::create(name: 'refresh_token', value: 'opaque-value')
            ->secure()
            ->httpOnly()
            ->withPath(path: '/v1/sessions')
            ->withDomain(domain: 'api.example.com')
            ->withMaxAge(seconds: 604800)
            ->partitioned()
            ->withSameSite(sameSite: SameSite::STRICT);

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the header includes every attribute in the canonical order */
        $expected = 'refresh_token=opaque-value; Max-Age=604800; Path=/v1/sessions; '
            . 'Domain=api.example.com; Secure; HttpOnly; SameSite=Strict; Partitioned';
        self::assertSame(['Set-Cookie' => [$expected]], $actual);
    }

    public function testWithMaxAgeWhenInvokedAfterWithExpiresThenOnlyMaxAgeIsEmitted(): void
    {
        /** @Given a cookie with an Expires attribute */
        $cookie = Cookie::create(name: 'session', value: 'abc')
            ->withExpires(expires: new DateTimeImmutable('2030-01-15 12:00:00 UTC'));

        /** @When Max-Age is set afterwards */
        $updated = $cookie->withMaxAge(seconds: 3600);

        /** @Then only Max-Age is emitted; Expires is cleared */
        self::assertSame(['Set-Cookie' => ['session=abc; Max-Age=3600']], $updated->toArray());
    }

    public function testExpireWhenInvokedThenEmitsEmptyValueMaxAgeZeroAndExpiresEpoch(): void
    {
        /** @Given a cookie deletion bound to the path used when the cookie was issued */
        $cookie = Cookie::expire(name: 'refresh_token')->withPath(path: '/v1/sessions');

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then the header instructs the browser to discard the cookie via both Max-Age and Expires */
        $expected = 'refresh_token=; Max-Age=0; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/v1/sessions';
        self::assertSame(['Set-Cookie' => [$expected]], $actual);
    }

    public function testWithExpiresWhenInvokedAfterWithMaxAgeThenOnlyExpiresIsEmitted(): void
    {
        /** @Given a cookie with a Max-Age attribute */
        $cookie = Cookie::create(name: 'session', value: 'abc')->withMaxAge(seconds: 3600);

        /** @When Expires is set afterwards */
        $updated = $cookie->withExpires(expires: new DateTimeImmutable('2030-01-15 12:00:00 UTC'));

        /** @Then only Expires is emitted; Max-Age is cleared */
        self::assertSame(['Set-Cookie' => ['session=abc; Expires=Tue, 15 Jan 2030 12:00:00 GMT']], $updated->toArray());
    }

    public function testWithSameSiteWhenNoneGivenOnAlreadySecureCookieThenSerializesBothAttributes(): void
    {
        /** @Given a cookie with SameSite=None combined with Secure */
        $cookie = Cookie::create(name: 'session', value: 'abc')
            ->secure()
            ->withSameSite(sameSite: SameSite::NONE);

        /** @When the header is serialized */
        $actual = $cookie->toArray();

        /** @Then both attributes are present */
        self::assertSame(['Set-Cookie' => ['session=abc; Secure; SameSite=None']], $actual);
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

    public static function invalidPathProvider(): array
    {
        return [
            'Path with semicolon'    => ['/api;evil'],
            'Path with comma'        => ['/api,evil'],
            'Path with control char' => ["/api\x00evil"]
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

    public static function invalidDomainProvider(): array
    {
        return [
            'Domain with space'        => ['example .com'],
            'Domain with tab'          => ["example\tcom"],
            'Domain with semicolon'    => ['example.com;evil'],
            'Domain with comma'        => ['example.com,evil'],
            'Domain with double quote' => ['"example.com"'],
            'Domain with backslash'    => ['example\\com'],
            'Domain with control char' => ["example\x00.com"]
        ];
    }
}
