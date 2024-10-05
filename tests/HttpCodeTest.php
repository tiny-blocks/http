<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpCodeTest extends TestCase
{
    #[DataProvider('messagesDataProvider')]
    public function testMessage(HttpCode $httpCode, string $expected): void
    {
        /** @Given an HttpCode instance */
        /** @When retrieving the message for the HttpCode */
        $actual = $httpCode->message();

        /** @Then the message should match the expected string */
        self::assertEquals($expected, $actual);
    }

    #[DataProvider('httpCodesDataProvider')]
    public function testIsHttpCode(int $httpCode, bool $expected): void
    {
        /** @Given an integer representing an HTTP code */
        /** @When checking if it is a valid HTTP code */
        $actual = HttpCode::isHttpCode(httpCode: $httpCode);

        /** @Then the result should match the expected boolean */
        self::assertEquals($expected, $actual);
    }

    public static function messagesDataProvider(): array
    {
        return [
            'OK message'                            => [
                'httpCode' => HttpCode::OK,
                'expected' => '200 OK'
            ],
            'Created message'                       => [
                'httpCode' => HttpCode::CREATED,
                'expected' => '201 Created'
            ],
            'Continue message'                      => [
                'httpCode' => HttpCode::CONTINUE,
                'expected' => '100 Continue'
            ],
            'Permanent Redirect message'            => [
                'httpCode' => HttpCode::PERMANENT_REDIRECT,
                'expected' => '308 Permanent Redirect'
            ],
            'Internal Server Error message'         => [
                'httpCode' => HttpCode::INTERNAL_SERVER_ERROR,
                'expected' => '500 Internal Server Error'
            ],
            'Non Authoritative Information message' => [
                'httpCode' => HttpCode::NON_AUTHORITATIVE_INFORMATION,
                'expected' => '203 Non Authoritative Information'
            ],
            'Proxy Authentication Required message' => [
                'httpCode' => HttpCode::PROXY_AUTHENTICATION_REQUIRED,
                'expected' => '407 Proxy Authentication Required'
            ],
        ];
    }

    public static function httpCodesDataProvider(): array
    {
        return [
            'Invalid code 0'                       => [
                'httpCode' => 0,
                'expected' => false
            ],
            'Invalid code -1'                      => [
                'httpCode' => -1,
                'expected' => false
            ],
            'Invalid code 1054'                    => [
                'httpCode' => 1054,
                'expected' => false
            ],
            'Valid code 200 OK'                    => [
                'httpCode' => HttpCode::OK->value,
                'expected' => true
            ],
            'Valid code 100 Continue'              => [
                'httpCode' => HttpCode::CONTINUE->value,
                'expected' => true
            ],
            'Valid code 500 Internal Server Error' => [
                'httpCode' => HttpCode::INTERNAL_SERVER_ERROR->value,
                'expected' => true
            ]
        ];
    }
}
