<?php

namespace TinyBlocks\Http;

use PHPUnit\Framework\TestCase;

final class HttpCodeTest extends TestCase
{
    /**
     * @dataProvider providerForTestMessage
     */
    public function testMessage(HttpCode $httpCode, string $expected): void
    {
        $actual = $httpCode->message();

        self::assertEquals($expected, $actual);
    }

    public function providerForTestMessage(): array
    {
        return [
            [
                'httpCode' => HttpCode::CONTINUE,
                'expected' => '100 Continue'
            ],
            [
                'httpCode' => HttpCode::OK,
                'expected' => '200 Ok'
            ],
            [
                'httpCode' => HttpCode::CREATED,
                'expected' => '201 Created'
            ],
            [
                'httpCode' => HttpCode::NON_AUTHORITATIVE_INFORMATION,
                'expected' => '203 Non Authoritative Information'
            ],
            [
                'httpCode' => HttpCode::PERMANENT_REDIRECT,
                'expected' => '308 Permanent Redirect'
            ],
            [
                'httpCode' => HttpCode::PERMANENT_REDIRECT,
                'expected' => '308 Permanent Redirect'
            ],
            [
                'httpCode' => HttpCode::PROXY_AUTHENTICATION_REQUIRED,
                'expected' => '407 Proxy Authentication Required'
            ],
            [
                'httpCode' => HttpCode::INTERNAL_SERVER_ERROR,
                'expected' => '500 Internal Server Error'
            ]
        ];
    }
}
