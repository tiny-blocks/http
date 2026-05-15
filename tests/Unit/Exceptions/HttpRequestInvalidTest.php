<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;
use TinyBlocks\Http\Method;

final class HttpRequestInvalidTest extends TestCase
{
    public function testFromBuildsExceptionWithAllFields(): void
    {
        /** @Given a URL, method, and reason */
        $url = 'https://api.example.com/dragons';
        $method = Method::PATCH;
        $reason = 'Malformed URI.';

        /** @When constructing the exception */
        $exception = HttpRequestInvalid::from(url: $url, method: $method, reason: $reason);

        /** @Then it is an instance of HttpRequestFailed and carries the correct fields */
        self::assertInstanceOf(HttpRequestFailed::class, $exception);
        self::assertSame($url, $exception->url());
        self::assertSame($method, $exception->method());
        self::assertSame($reason, $exception->reason());
        self::assertSame($reason, $exception->getMessage());
    }

    public function testFromChainsPreviousThrowable(): void
    {
        /** @Given a previous throwable */
        $previous = new RuntimeException('bad request object');

        /** @When constructing with a previous */
        $exception = HttpRequestInvalid::from(
            url: 'https://api.example.com',
            method: Method::PUT,
            reason: 'Request is invalid.',
            previous: $previous
        );

        /** @Then the chain is preserved */
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testFromClientExceptionBuildsFromRequestException(): void
    {
        /** @Given a request and a request exception */
        $request = Request::create(url: 'https://api.example.com/dragons', method: Method::POST);
        $requestException = new class ('bad URI') extends RuntimeException implements RequestExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return (new Psr17Factory())->createRequest('POST', 'https://api.example.com');
            }
        };

        /** @When constructing from a client exception */
        $exception = HttpRequestInvalid::fromClientException(request: $request, exception: $requestException);

        /** @Then the exception reflects the request and wraps the original */
        self::assertSame('https://api.example.com/dragons', $exception->url());
        self::assertSame($requestException, $exception->getPrevious());
        self::assertInstanceOf(HttpException::class, $exception);
    }

    public function testExceptionIsCatchableAsHttpRequestFailed(): void
    {
        /** @Given an HttpRequestInvalid exception */
        $exception = HttpRequestInvalid::from(
            url: 'https://api.example.com',
            method: Method::GET,
            reason: 'Invalid.'
        );

        /** @Then it is catchable as HttpRequestFailed */
        self::assertInstanceOf(HttpRequestFailed::class, $exception);
    }
}
