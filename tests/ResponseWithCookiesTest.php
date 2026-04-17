<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\Response;
use TinyBlocks\Http\ResponseCacheDirectives;
use TinyBlocks\Http\SameSite;

final class ResponseWithCookiesTest extends TestCase
{
    public function testResponseWithSingleCookie(): void
    {
        /** @Given a fully configured cookie */
        $cookie = Cookie::create(name: 'session', value: 'abc')
            ->httpOnly()
            ->secure()
            ->withSameSite(sameSite: SameSite::STRICT)
            ->withPath(path: '/')
            ->withMaxAge(seconds: 604800);

        /** @When the response is built with the cookie */
        $response = Response::ok(['ok' => true], $cookie);

        /** @Then the Set-Cookie header should reflect the cookie configuration */
        self::assertSame(
            ['session=abc; Max-Age=604800; Path=/; Secure; HttpOnly; SameSite=Strict'],
            $response->getHeader('Set-Cookie')
        );
    }

    public function testResponseWithMultipleCookiesPreservesEachOne(): void
    {
        /** @Given an access cookie */
        $accessCookie = Cookie::create(name: 'access_token', value: 'aaa')
            ->httpOnly()
            ->secure()
            ->withPath(path: '/');

        /** @And a refresh cookie */
        $refreshCookie = Cookie::create(name: 'refresh_token', value: 'bbb')
            ->httpOnly()
            ->secure()
            ->withSameSite(sameSite: SameSite::STRICT)
            ->withPath(path: '/v1/sessions')
            ->withMaxAge(seconds: 604800);

        /** @When the response is built with both cookies */
        $response = Response::ok(['ok' => true], $accessCookie, $refreshCookie);

        /** @Then both Set-Cookie header values should be present */
        $setCookieHeaders = $response->getHeader('Set-Cookie');
        self::assertCount(2, $setCookieHeaders);
        self::assertSame('access_token=aaa; Path=/; Secure; HttpOnly', $setCookieHeaders[0]);
        self::assertSame(
            'refresh_token=bbb; Max-Age=604800; Path=/v1/sessions; Secure; HttpOnly; SameSite=Strict',
            $setCookieHeaders[1]
        );
    }

    public function testResponseWithCookiesCoexistsWithOtherHeaders(): void
    {
        /** @Given a cookie */
        $cookie = Cookie::create(name: 'session', value: 'abc')->httpOnly()->secure();

        /** @And a content type */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And a cache control directive */
        $cacheControl = CacheControl::fromResponseDirectives(ResponseCacheDirectives::noCache());

        /** @When the response is built with all of them */
        $response = Response::ok(['ok' => true], $contentType, $cacheControl, $cookie);

        /** @Then every header should be preserved */
        self::assertSame(['application/json; charset=utf-8'], $response->getHeader('Content-Type'));
        self::assertSame(['no-cache'], $response->getHeader('Cache-Control'));
        self::assertSame(['session=abc; Secure; HttpOnly'], $response->getHeader('Set-Cookie'));
    }

    public function testResponseWithExpireCookieInstructsBrowserToDiscard(): void
    {
        /** @Given an expiration cookie with the same path used on set */
        $cookie = Cookie::expire(name: 'refresh_token')
            ->httpOnly()
            ->secure()
            ->withSameSite(sameSite: SameSite::STRICT)
            ->withPath(path: '/v1/sessions');

        /** @When a no-content response is built with the cookie */
        $response = Response::noContent($cookie);

        /** @Then the Set-Cookie header should instruct the browser to discard the cookie */
        self::assertSame(
            ['refresh_token=; Max-Age=0; Path=/v1/sessions; Secure; HttpOnly; SameSite=Strict'],
            $response->getHeader('Set-Cookie')
        );
    }
}
