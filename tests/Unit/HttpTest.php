<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Test\TinyBlocks\Http\Fixtures\Client\CapturingClient;
use Test\TinyBlocks\Http\Fixtures\Client\ThrowingClient;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;
use TinyBlocks\Http\Exceptions\MalformedPath;
use TinyBlocks\Http\Http;
use TinyBlocks\Http\Method;

final class HttpTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testSendWhenTransportRespondsThenReturnsResponseWithMatchingCode(): void
    {
        /** @Given a transport seeded with a 200 response */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @When sending a valid request */
        $response = $http->send(request: Request::create(url: '/dragons'));

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testSendWhenBaseUrlEndsWithSlashAndPathLeadsWithSlashThenNoDoubleSlash(): void
    {
        /** @Given a transport seeded with a 200 response and a base URL ending in slash */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com/')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @When sending a request whose path starts with a slash */
        $response = $http->send(request: Request::create(url: '/dragons'));

        /** @Then the response is returned without double slash in the URL */
        self::assertSame(Code::OK, $response->code());
    }

    public function testSendWhenQueryGivenThenAppendsAsRfc3986(): void
    {
        /** @Given a transport seeded with a 200 response */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @When sending a request with query parameters */
        $response = $http->send(
            request: Request::create(url: '/dragons', query: ['sort' => 'name', 'order' => 'asc'])
        );

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testSendWhenBodyGivenThenSendsJsonPayload(): void
    {
        /** @Given a transport seeded with a 201 response */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 201),
                factory: $this->factory
            ))
            ->build();

        /** @When sending a request with a JSON body */
        $response = $http->send(
            request: Request::create(url: '/dragons', body: ['name' => 'Hydra'], method: Method::POST)
        );

        /** @Then the response code is correct */
        self::assertSame(Code::CREATED, $response->code());
    }

    public function testSendWhenClientRaisesNetworkExceptionThenThrowsHttpNetworkFailed(): void
    {
        /** @Given a PSR-18 client that throws NetworkExceptionInterface */
        $networkException = new class ('connection refused') extends RuntimeException implements
            NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return new Psr17Factory()->createRequest('GET', 'https://api.example.com');
            }
        };

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: $networkException),
                factory: $this->factory
            ))
            ->build();

        /** @Then HttpNetworkFailed is thrown */
        $this->expectException(HttpNetworkFailed::class);

        /** @When sending the request */
        $http->send(request: Request::create(url: '/dragons'));
    }

    public function testSendWhenClientRaisesRequestExceptionThenThrowsHttpRequestInvalid(): void
    {
        /** @Given a PSR-18 client that throws RequestExceptionInterface */
        $requestException = new class ('bad request') extends RuntimeException implements RequestExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return new Psr17Factory()->createRequest('GET', 'https://api.example.com');
            }
        };

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: $requestException),
                factory: $this->factory
            ))
            ->build();

        /** @Then HttpRequestInvalid is thrown */
        $this->expectException(HttpRequestInvalid::class);

        /** @When sending the request */
        $http->send(request: Request::create(url: '/dragons'));
    }

    public function testSendWhenGenericClientExceptionRaisedThenThrowsHttpRequestFailed(): void
    {
        /** @Given a PSR-18 client that throws a generic ClientExceptionInterface */
        $clientException = new class ('generic failure') extends RuntimeException implements ClientExceptionInterface {
        };

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: $clientException),
                factory: $this->factory
            ))
            ->build();

        /** @Then HttpRequestFailed is thrown */
        $this->expectException(HttpRequestFailed::class);

        /** @When sending the request */
        $http->send(request: Request::create(url: '/dragons'));
    }

    public function testSendWhenProtocolRelativePathGivenThenThrowsMalformedPath(): void
    {
        /** @Given an Http instance with a base URL */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @Then MalformedPath is thrown without invoking the transport */
        $this->expectException(MalformedPath::class);

        /** @When sending a request whose path is protocol-relative */
        $http->send(request: Request::create(url: '//evil.example.com/attack'));
    }

    public function testSendWhenSchemePathGivenThenThrowsMalformedPath(): void
    {
        /** @Given an Http instance with a base URL */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @Then MalformedPath is thrown */
        $this->expectException(MalformedPath::class);

        /** @When sending a request whose path contains a scheme */
        $http->send(request: Request::create(url: 'javascript:alert(1)'));
    }

    public function testSendWhenControlCharsInPathGivenThenThrowsMalformedPath(): void
    {
        /** @Given an Http instance with a base URL */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @Then MalformedPath is thrown */
        $this->expectException(MalformedPath::class);

        /** @When sending a request whose path contains control characters */
        $http->send(request: Request::create(url: "/dragons\x00/evil"));
    }

    public function testSendWhenNetworkExceptionRaisedThenPreservesPreviousChain(): void
    {
        /** @Given a network exception */
        $networkException = new class ('timeout') extends RuntimeException implements NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return new Psr17Factory()->createRequest('GET', 'https://api.example.com');
            }
        };

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: $networkException),
                factory: $this->factory
            ))
            ->build();

        /** @When sending the request */
        try {
            $http->send(request: Request::create(url: '/dragons'));
            self::fail('HttpNetworkFailed was expected.');
        } catch (HttpNetworkFailed $exception) {
            /** @Then the previous exception is preserved in the chain */
            self::assertSame($networkException, $exception->getPrevious());
        }
    }

}
