<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Method;

final class HttpRequestFailedTest extends TestCase
{
    public function testFromBuildsExceptionWithAllFields(): void
    {
        /** @Given a URL, method, and reason */
        $url = 'https://api.example.com/dragons';
        $method = Method::POST;
        $reason = 'Connection refused.';

        /** @When constructing the exception */
        $exception = HttpRequestFailed::from(url: $url, method: $method, reason: $reason);

        /** @Then methods and message are correct */
        self::assertSame($url, $exception->url());
        self::assertSame($method, $exception->method());
        self::assertSame($reason, $exception->reason());
        self::assertSame($reason, $exception->getMessage());
        self::assertNull($exception->getPrevious());
    }

    public function testFromChainsPreviousThrowable(): void
    {
        /** @Given a previous throwable */
        $previous = new RuntimeException('root cause');

        /** @When constructing the exception with a previous */
        $exception = HttpRequestFailed::from(
            url: 'https://api.example.com',
            method: Method::GET,
            reason: 'Failed.',
            previous: $previous
        );

        /** @Then the previous is preserved in the chain */
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testFromClientExceptionBuildsExceptionFromRequest(): void
    {
        /** @Given a request and a client exception */
        $request = Request::create(url: 'https://api.example.com/dragons', method: Method::DELETE);
        $clientException = new class ('PSR-18 error') extends RuntimeException implements ClientExceptionInterface {
        };

        /** @When constructing from a client exception */
        $exception = HttpRequestFailed::fromClientException(request: $request, exception: $clientException);

        /** @Then the exception reflects the request and wraps the original */
        self::assertSame('https://api.example.com/dragons', $exception->url());
        self::assertSame(Method::DELETE, $exception->method());
        self::assertSame($clientException, $exception->getPrevious());
        self::assertInstanceOf(HttpException::class, $exception);
    }

    public function testFromJsonErrorBuildsExceptionFromRequest(): void
    {
        /** @Given a request and a JSON exception */
        $request = Request::create(url: 'https://api.example.com/dragons', method: Method::POST);
        $jsonException = new JsonException('Malformed UTF-8');

        /** @When constructing from a JSON error */
        $exception = HttpRequestFailed::fromJsonError(request: $request, exception: $jsonException);

        /** @Then the exception reflects the encoding failure */
        self::assertSame('https://api.example.com/dragons', $exception->url());
        self::assertStringContainsString('Failed to encode request body', $exception->reason());
        self::assertSame($jsonException, $exception->getPrevious());
    }

    public function testExceptionIsInstanceOfHttpException(): void
    {
        /** @When building an HttpRequestFailed */
        $exception = HttpRequestFailed::from(
            url: 'https://api.example.com',
            method: Method::GET,
            reason: 'Failure.'
        );

        /** @Then it implements HttpException */
        self::assertInstanceOf(HttpException::class, $exception);
    }

    public function testExceptionCodeIsZero(): void
    {
        /** @When building an HttpRequestFailed */
        $exception = HttpRequestFailed::from(
            url: 'https://api.example.com',
            method: Method::GET,
            reason: 'Failure.'
        );

        /** @Then the exception code is zero */
        self::assertSame(0, $exception->getCode());
    }
}
