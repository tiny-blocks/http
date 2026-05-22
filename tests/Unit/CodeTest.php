<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Code;

final class CodeTest extends TestCase
{
    #[DataProvider('messagesDataProvider')]
    public function testMessageWhenKnownCodeGivenThenReturnsRfcDescription(Code $code, string $expected): void
    {
        /** @Given a Code instance */
        /** @When retrieving the message for the Code */
        $actual = $code->message();

        /** @Then the message matches the expected string */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('codesDataProvider')]
    public function testIsValidCodeWhenIntegerGivenThenReturnsExpected(int $code, bool $expected): void
    {
        /** @Given an integer representing an HTTP code */
        /** @When checking if it is a valid HTTP code */
        $actual = Code::isValidCode(code: $code);

        /** @Then the result matches the expected boolean */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('errorCodesDataProvider')]
    public function testIsErrorCodeWhenIntegerGivenThenReturnsExpected(int $code, bool $expected): void
    {
        /** @Given an HTTP status code */
        /** @When checking if it is an error code (4xx or 5xx) */
        $actual = Code::isErrorCode(code: $code);

        /** @Then the result matches the expected boolean */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('successCodesDataProvider')]
    public function testIsSuccessCodeWhenIntegerGivenThenReturnsExpected(int $code, bool $expected): void
    {
        /** @Given an HTTP status code */
        /** @When checking if it is a success code (2xx) */
        $actual = Code::isSuccessCode(code: $code);

        /** @Then the result matches the expected boolean */
        self::assertSame($expected, $actual);
    }

    public function testIsSuccessWhenCodeOkGivenThenReturnsTrue(): void
    {
        /** @Given Code::OK */
        $code = Code::OK;

        /** @When invoking isSuccess */
        $actual = $code->isSuccess();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsErrorWhenCodeOkGivenThenReturnsFalse(): void
    {
        /** @Given Code::OK */
        $code = Code::OK;

        /** @When invoking isError */
        $actual = $code->isError();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public function testIsErrorWhenCodeInternalServerErrorGivenThenReturnsTrue(): void
    {
        /** @Given Code::INTERNAL_SERVER_ERROR */
        $code = Code::INTERNAL_SERVER_ERROR;

        /** @When invoking isError */
        $actual = $code->isError();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsSuccessWhenCodeInternalServerErrorGivenThenReturnsFalse(): void
    {
        /** @Given Code::INTERNAL_SERVER_ERROR */
        $code = Code::INTERNAL_SERVER_ERROR;

        /** @When invoking isSuccess */
        $actual = $code->isSuccess();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public function testIsInformationalWhenCodeContinueGivenThenReturnsTrue(): void
    {
        /** @Given Code::CONTINUE */
        $code = Code::CONTINUE;

        /** @When invoking isInformational */
        $actual = $code->isInformational();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsInformationalWhenCodeEarlyHintsGivenThenReturnsTrue(): void
    {
        /** @Given Code::EARLY_HINTS */
        $code = Code::EARLY_HINTS;

        /** @When invoking isInformational */
        $actual = $code->isInformational();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsInformationalWhenCodeOkGivenThenReturnsFalse(): void
    {
        /** @Given Code::OK */
        $code = Code::OK;

        /** @When invoking isInformational */
        $actual = $code->isInformational();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public function testIsRedirectionWhenCodeMovedPermanentlyGivenThenReturnsTrue(): void
    {
        /** @Given Code::MOVED_PERMANENTLY */
        $code = Code::MOVED_PERMANENTLY;

        /** @When invoking isRedirection */
        $actual = $code->isRedirection();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsRedirectionWhenCodeMultipleChoicesGivenThenReturnsTrue(): void
    {
        /** @Given Code::MULTIPLE_CHOICES */
        $code = Code::MULTIPLE_CHOICES;

        /** @When invoking isRedirection */
        $actual = $code->isRedirection();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsRedirectionWhenCodePermanentRedirectGivenThenReturnsTrue(): void
    {
        /** @Given Code::PERMANENT_REDIRECT */
        $code = Code::PERMANENT_REDIRECT;

        /** @When invoking isRedirection */
        $actual = $code->isRedirection();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsRedirectionWhenCodeOkGivenThenReturnsFalse(): void
    {
        /** @Given Code::OK */
        $code = Code::OK;

        /** @When invoking isRedirection */
        $actual = $code->isRedirection();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public function testIsClientErrorWhenCodeBadRequestGivenThenReturnsTrue(): void
    {
        /** @Given Code::BAD_REQUEST */
        $code = Code::BAD_REQUEST;

        /** @When invoking isClientError */
        $actual = $code->isClientError();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsClientErrorWhenCodeUnavailableForLegalReasonsGivenThenReturnsTrue(): void
    {
        /** @Given Code::UNAVAILABLE_FOR_LEGAL_REASONS */
        $code = Code::UNAVAILABLE_FOR_LEGAL_REASONS;

        /** @When invoking isClientError */
        $actual = $code->isClientError();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsClientErrorWhenCodeInternalServerErrorGivenThenReturnsFalse(): void
    {
        /** @Given Code::INTERNAL_SERVER_ERROR */
        $code = Code::INTERNAL_SERVER_ERROR;

        /** @When invoking isClientError */
        $actual = $code->isClientError();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public function testIsServerErrorWhenCodeInternalServerErrorGivenThenReturnsTrue(): void
    {
        /** @Given Code::INTERNAL_SERVER_ERROR */
        $code = Code::INTERNAL_SERVER_ERROR;

        /** @When invoking isServerError */
        $actual = $code->isServerError();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsServerErrorWhenCodeNetworkAuthenticationRequiredGivenThenReturnsTrue(): void
    {
        /** @Given Code::NETWORK_AUTHENTICATION_REQUIRED */
        $code = Code::NETWORK_AUTHENTICATION_REQUIRED;

        /** @When invoking isServerError */
        $actual = $code->isServerError();

        /** @Then the result is true */
        self::assertTrue($actual);
    }

    public function testIsServerErrorWhenCodeBadRequestGivenThenReturnsFalse(): void
    {
        /** @Given Code::BAD_REQUEST */
        $code = Code::BAD_REQUEST;

        /** @When invoking isServerError */
        $actual = $code->isServerError();

        /** @Then the result is false */
        self::assertFalse($actual);
    }

    public static function messagesDataProvider(): array
    {
        return [
            'OK message'                              => [
                'code'     => Code::OK,
                'expected' => 'OK'
            ],
            'Created message'                         => [
                'code'     => Code::CREATED,
                'expected' => 'Created'
            ],
            'IM Used message'                         => [
                'code'     => Code::IM_USED,
                'expected' => 'IM Used'
            ],
            'Continue message'                        => [
                'code'     => Code::CONTINUE,
                'expected' => 'Continue'
            ],
            "I'm a teapot message"                    => [
                'code'     => Code::IM_A_TEAPOT,
                'expected' => "I'm a teapot"
            ],
            'Permanent Redirect message'              => [
                'code'     => Code::PERMANENT_REDIRECT,
                'expected' => 'Permanent Redirect'
            ],
            'Internal Server Error message'           => [
                'code'     => Code::INTERNAL_SERVER_ERROR,
                'expected' => 'Internal Server Error'
            ],
            'Non Authoritative Information message'   => [
                'code'     => Code::NON_AUTHORITATIVE_INFORMATION,
                'expected' => 'Non Authoritative Information'
            ],
            'Proxy Authentication Required message'   => [
                'code'     => Code::PROXY_AUTHENTICATION_REQUIRED,
                'expected' => 'Proxy Authentication Required'
            ],
            'Network Authentication Required message' => [
                'code'     => Code::NETWORK_AUTHENTICATION_REQUIRED,
                'expected' => 'Network Authentication Required'
            ]
        ];
    }

    public static function codesDataProvider(): array
    {
        return [
            'Invalid code 0'                       => ['code' => 0, 'expected' => false],
            'Invalid code -1'                      => ['code' => -1, 'expected' => false],
            'Invalid code 1054'                    => ['code' => 1054, 'expected' => false],
            'Valid code 200 OK'                    => ['code' => Code::OK->value, 'expected' => true],
            'Valid code 100 Continue'              => ['code' => Code::CONTINUE->value, 'expected' => true],
            'Valid code 500 Internal Server Error' => ['code' => Code::INTERNAL_SERVER_ERROR->value, 'expected' => true]
        ];
    }

    public static function errorCodesDataProvider(): array
    {
        return [
            'Code 200 OK'                              => ['code' => 200, 'expected' => false],
            'Code 400 Bad Request'                     => ['code' => 400, 'expected' => true],
            'Code 500 Internal Server Error'           => ['code' => 500, 'expected' => true],
            'Code 511 Network Authentication Required' => ['code' => 511, 'expected' => true]
        ];
    }

    public static function successCodesDataProvider(): array
    {
        return [
            'Code 200 OK'                    => ['code' => 200, 'expected' => true],
            'Code 201 Created'               => ['code' => 201, 'expected' => true],
            'Code 226 IM Used'               => ['code' => 226, 'expected' => true],
            'Code 500 Internal Server Error' => ['code' => 500, 'expected' => false]
        ];
    }
}
