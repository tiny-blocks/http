<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Transports;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\Http\Unit\CapturingClient;
use Test\TinyBlocks\Http\Unit\PsrClientException;
use Test\TinyBlocks\Http\Unit\PsrNetworkException;
use Test\TinyBlocks\Http\Unit\PsrRequestException;
use Test\TinyBlocks\Http\Unit\ThrowingClient;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;
use TinyBlocks\Http\Headers;

final class NetworkTransportTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testSendWhenNoBodyGivenThenForwardsEmptyBody(): void
    {
        /** @Given a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request without body */
        $transport->send(request: Request::get(url: 'https://api.example.com/dragons'));

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
            request: Request::get(url: 'https://api.example.com/dragons')
                ->withMergedHeaders(defaults: Headers::fromArray(entries: ['X-Correlation-ID' => 'abc-123']))
        );

        /** @Then the PSR-7 request carries the custom header */
        self::assertNotNull($client->captured);
        self::assertSame('abc-123', $client->captured->getHeaderLine('X-Correlation-ID'));
    }

    public function testSendWhenBodyGivenThenForwardsJsonAndContentTypeHeader(): void
    {
        /** @Given a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 201);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request with a JSON body and a Content-Type default */
        $transport->send(
            request: Request::post(url: 'https://api.example.com/dragons', body: ['name' => 'Hydra'])
                ->withMergedHeaders(defaults: Headers::fromArray(entries: ['Content-Type' => 'application/json']))
        );

        /** @Then the PSR-7 request carries JSON and the Content-Type header */
        self::assertNotNull($client->captured);
        self::assertSame('{"name":"Hydra"}', (string)$client->captured->getBody());
        self::assertSame('application/json', $client->captured->getHeaderLine('Content-Type'));
    }

    public function testSendWhenBodyHasInvalidUtf8ThenSubstitutesAndStillSends(): void
    {
        /** @Given a transport configured with a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request whose body contains a non-UTF-8 byte sequence */
        $transport->send(
            request: Request::post(url: 'https://api.example.com/dragons', body: ['value' => "\xB0\xB1\xB2"])
        );

        /** @Then the PSR-7 request body carries the JSON-escaped replacement character */
        self::assertNotNull($client->captured);
        self::assertStringContainsString('\ufffd', (string)$client->captured->getBody());
    }

    public function testSendWhenSuccessfulPsrResponseGivenThenWrapsInClientResponse(): void
    {
        /** @Given a client that returns a 200 response */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $transport = NetworkTransport::with(client: $client, factory: $this->factory);

        /** @When sending a request */
        $response = $transport->send(request: Request::get(url: 'https://api.example.com/dragons'));

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testSendWhenClientRaisesNetworkExceptionThenThrowsHttpNetworkFailed(): void
    {
        /** @Given a PSR-18 client that throws NetworkExceptionInterface */
        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: new PsrNetworkException('connection refused')),
            factory: $this->factory
        );

        /** @Then HttpNetworkFailed is thrown */
        $this->expectException(HttpNetworkFailed::class);

        /** @When sending the request */
        $transport->send(request: Request::get(url: 'https://api.example.com/dragons'));
    }

    public function testSendWhenClientRaisesRequestExceptionThenThrowsHttpRequestInvalid(): void
    {
        /** @Given a PSR-18 client that throws RequestExceptionInterface */
        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: new PsrRequestException('bad request')),
            factory: $this->factory
        );

        /** @Then HttpRequestInvalid is thrown */
        $this->expectException(HttpRequestInvalid::class);

        /** @When sending the request */
        $transport->send(request: Request::get(url: 'https://api.example.com/dragons'));
    }

    public function testSendWhenClientRaisesGenericClientExceptionThenThrowsHttpRequestFailed(): void
    {
        /** @Given a PSR-18 client that throws a generic ClientExceptionInterface */
        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: new PsrClientException('generic failure')),
            factory: $this->factory
        );

        /** @Then HttpRequestFailed is thrown */
        $this->expectException(HttpRequestFailed::class);

        /** @When sending the request */
        $transport->send(request: Request::get(url: 'https://api.example.com/dragons'));
    }

    public function testSendWhenClientRaisesRequestExceptionThenExceptionMessageDescribesInvalidRequest(): void
    {
        /** @Given a transport whose client throws RequestExceptionInterface */
        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: new PsrRequestException('bad request')),
            factory: $this->factory
        );

        try {
            /** @When sending the request */
            $transport->send(request: Request::post(url: 'https://api.example.com/dragons'));
            self::fail('HttpRequestInvalid was expected.');
        } catch (HttpRequestInvalid $exception) {
            /** @Then the message names the method, the URL, and the client-supplied reason */
            self::assertStringContainsString('POST', $exception->getMessage());
            self::assertStringContainsString('https://api.example.com/dragons', $exception->getMessage());
            self::assertStringContainsString('bad request', $exception->getMessage());
        }
    }

    public function testSendWhenClientRaisesGenericClientExceptionThenExceptionMessageDescribesClientFailure(): void
    {
        /** @Given a transport whose client throws a generic ClientExceptionInterface */
        $transport = NetworkTransport::with(
            client: ThrowingClient::throwing(exception: new PsrClientException('generic failure')),
            factory: $this->factory
        );

        try {
            /** @When sending the request */
            $transport->send(request: Request::delete(url: 'https://api.example.com/dragons'));
            self::fail('HttpRequestFailed was expected.');
        } catch (HttpRequestFailed $exception) {
            /** @Then the message names the method, the URL, and the client-supplied reason */
            self::assertStringContainsString('DELETE', $exception->getMessage());
            self::assertStringContainsString('https://api.example.com/dragons', $exception->getMessage());
            self::assertStringContainsString('generic failure', $exception->getMessage());
        }
    }
}
