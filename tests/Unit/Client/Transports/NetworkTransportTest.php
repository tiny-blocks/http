<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Transports;

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
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Method;

final class NetworkTransportTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testSendWhenBodyGivenThenForwardsJsonAndContentTypeHeader(): void
    {
        /** @Given a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 201);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request with a JSON body and a Content-Type default */
        $transport->send(
            request: Request::create(
                url: 'https://api.example.com/dragons',
                body: ['name' => 'Hydra'],
                method: Method::POST
            )->withMergedHeaders(defaults: new Headers(entries: ['Content-Type' => 'application/json']))
        );

        /** @Then the PSR-7 request carries JSON and the Content-Type header */
        self::assertNotNull($client->captured);
        self::assertSame('{"name":"Hydra"}', (string)$client->captured->getBody());
        self::assertSame('application/json', $client->captured->getHeaderLine('Content-Type'));
    }

    public function testSendWhenNoBodyGivenThenForwardsEmptyBody(): void
    {
        /** @Given a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request without body */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));

        /** @Then the PSR-7 request body is empty */
        self::assertNotNull($client->captured);
        self::assertSame('', (string)$client->captured->getBody());
    }

    public function testSendWhenCustomHeaderMergedThenForwardsToPsrRequest(): void
    {
        /** @Given a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request with a custom header merged in */
        $transport->send(
            request: Request::create(url: 'https://api.example.com/dragons')
                ->withMergedHeaders(defaults: new Headers(entries: ['X-Correlation-ID' => 'abc-123']))
        );

        /** @Then the PSR-7 request carries the custom header */
        self::assertNotNull($client->captured);
        self::assertSame('abc-123', $client->captured->getHeaderLine('X-Correlation-ID'));
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

        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: $networkException),
            factory: $this->factory
        );

        /** @Then HttpNetworkFailed is thrown */
        $this->expectException(HttpNetworkFailed::class);

        /** @When sending the request */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));
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

        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: $requestException),
            factory: $this->factory
        );

        /** @Then HttpRequestInvalid is thrown */
        $this->expectException(HttpRequestInvalid::class);

        /** @When sending the request */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));
    }

    public function testSendWhenClientRaisesGenericClientExceptionThenThrowsHttpRequestFailed(): void
    {
        /** @Given a PSR-18 client that throws a generic ClientExceptionInterface */
        $clientException = new class ('generic failure') extends RuntimeException implements ClientExceptionInterface {
        };

        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: $clientException),
            factory: $this->factory
        );

        /** @Then HttpRequestFailed is thrown */
        $this->expectException(HttpRequestFailed::class);

        /** @When sending the request */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));
    }

    public function testSendWhenSuccessfulPsrResponseGivenThenWrapsInClientResponse(): void
    {
        /** @Given a client that returns a 200 response */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request */
        $response = $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testSendWhenBodyHasInvalidUtf8ThenSubstitutesAndStillSends(): void
    {
        /** @Given a transport configured with a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request whose body contains a non-UTF-8 byte sequence */
        $transport->send(
            request: Request::create(
                url: 'https://api.example.com/dragons',
                body: ['value' => "\xB0\xB1\xB2"],
                method: Method::POST
            )
        );

        /** @Then the PSR-7 request body carries the JSON-escaped replacement character */
        self::assertNotNull($client->captured);
        self::assertStringContainsString('\ufffd', (string)$client->captured->getBody());
    }
}
