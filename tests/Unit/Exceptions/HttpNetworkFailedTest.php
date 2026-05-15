<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Exceptions;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Method;

final class HttpNetworkFailedTest extends TestCase
{
    public function testFromWhenAllFieldsGivenThenExposesEveryAccessor(): void
    {
        /** @Given a URL, method, and reason */
        $url = 'https://api.example.com/dragons';
        $method = Method::GET;
        $reason = 'Network unreachable.';

        /** @When constructing the exception */
        $exception = HttpNetworkFailed::from(url: $url, method: $method, reason: $reason);

        /** @Then it carries the correct fields and is recognized as HttpException */
        self::assertInstanceOf(HttpException::class, $exception);
        self::assertSame($url, $exception->url());
        self::assertSame($method, $exception->method());
        self::assertSame($reason, $exception->reason());
        self::assertStringContainsString($reason, $exception->getMessage());
        self::assertStringContainsString('GET', $exception->getMessage());
        self::assertStringContainsString($url, $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    public function testFromWhenPreviousGivenThenPreservesChain(): void
    {
        /** @Given a previous throwable */
        $previous = new RuntimeException('socket error');

        /** @When constructing with a previous */
        $exception = HttpNetworkFailed::from(
            url: 'https://api.example.com',
            method: Method::DELETE,
            reason: 'Network failed.',
            previous: $previous
        );

        /** @Then the chain is preserved */
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testFromClientExceptionWhenNetworkExceptionGivenThenWrapsOriginal(): void
    {
        /** @Given a request and a network exception */
        $request = Request::create(url: 'https://api.example.com/dragons');
        $networkException = new class ('DNS failure') extends RuntimeException implements NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return (new Psr17Factory())->createRequest('GET', 'https://api.example.com');
            }
        };

        /** @When constructing from a client exception */
        $exception = HttpNetworkFailed::fromClientException(request: $request, exception: $networkException);

        /** @Then the exception wraps the original and implements HttpException */
        self::assertSame('https://api.example.com/dragons', $exception->url());
        self::assertSame($networkException, $exception->getPrevious());
        self::assertInstanceOf(HttpException::class, $exception);
    }
}
