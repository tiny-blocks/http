<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Method;

final class HttpRequestFailedTest extends TestCase
{
    public function testFromWhenAllFieldsGivenThenExposesEveryAccessor(): void
    {
        /** @Given a URL, method, and reason */
        $url = 'https://api.example.com/dragons';
        $method = Method::POST;
        $reason = 'Connection refused.';

        /** @When constructing the exception */
        $exception = HttpRequestFailed::from(url: $url, method: $method, reason: $reason);

        /** @Then methods, reason, and message are correct */
        self::assertSame($url, $exception->url());
        self::assertSame($method, $exception->method());
        self::assertSame($reason, $exception->reason());
        self::assertStringContainsString($reason, $exception->getMessage());
        self::assertNull($exception->getPrevious());
    }

    public function testFromWhenPreviousGivenThenPreservesChain(): void
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

    public function testFromClientExceptionWhenRequestGivenThenWrapsOriginal(): void
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

    public function testGetCodeWhenExceptionBuiltThenReturnsZero(): void
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
