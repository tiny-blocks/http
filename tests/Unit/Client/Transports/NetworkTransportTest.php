<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Transports;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;
use TinyBlocks\Http\Method;

final class NetworkTransportTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testRequestWithBodySendsJsonEncodedBodyAndContentTypeHeader(): void
    {
        /** @Given a client that captures the PSR-7 request */
        $captured = null;
        $client = $this->buildCapturingClient(captured: $captured, statusCode: 201);
        $transport = NetworkTransport::with(
            client: $client,
            factory: $this->factory
        );

        /** @When sending a request with a JSON body */
        $transport->send(
            request: Request::create(
                url: 'https://api.example.com/dragons',
                body: ['name' => 'Hydra'],
                method: Method::POST
            )->withMergedHeaders(defaults: ['Content-Type' => 'application/json'])
        );

        /** @Then the PSR-7 request carries JSON and the Content-Type header */
        self::assertSame('{"name":"Hydra"}', (string)$captured->getBody());
        self::assertSame('application/json', $captured->getHeaderLine('Content-Type'));
    }

    public function testRequestWithoutBodySendsNoBody(): void
    {
        /** @Given a client that captures the PSR-7 request */
        $captured = null;
        $client = $this->buildCapturingClient(captured: $captured, statusCode: 200);
        $transport = NetworkTransport::with(
            client: $client,
            factory: $this->factory
        );

        /** @When sending a request without body */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));

        /** @Then the PSR-7 request body is empty */
        self::assertSame('', (string)$captured->getBody());
    }

    public function testCustomHeadersAreForwardedToPsrRequest(): void
    {
        /** @Given a client that captures the PSR-7 request */
        $captured = null;
        $client = $this->buildCapturingClient(captured: $captured, statusCode: 200);
        $transport = NetworkTransport::with(
            client: $client,
            factory: $this->factory
        );

        /** @When sending a request with a custom header */
        $transport->send(
            request: Request::create(url: 'https://api.example.com/dragons')
                ->withMergedHeaders(defaults: ['X-Correlation-ID' => 'abc-123'])
        );

        /** @Then the PSR-7 request carries the custom header */
        self::assertSame('abc-123', $captured->getHeaderLine('X-Correlation-ID'));
    }

    public function testNetworkExceptionMapsToHttpNetworkFailed(): void
    {
        /** @Given a PSR-18 client that throws NetworkExceptionInterface */
        $networkException = new class ('connection refused') extends RuntimeException implements
            NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return (new Psr17Factory())->createRequest('GET', 'https://api.example.com');
            }
        };

        $transport = NetworkTransport::with(
            client: $this->buildThrowingClient(exception: $networkException),
            factory: $this->factory
        );

        /** @Then HttpNetworkFailed is thrown with previous set */
        $this->expectException(HttpNetworkFailed::class);

        /** @When sending the request */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));
    }

    public function testRequestExceptionMapsToHttpRequestInvalid(): void
    {
        /** @Given a PSR-18 client that throws RequestExceptionInterface */
        $requestException = new class ('bad request') extends RuntimeException implements RequestExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return (new Psr17Factory())->createRequest('GET', 'https://api.example.com');
            }
        };

        $transport = NetworkTransport::with(
            client: $this->buildThrowingClient(exception: $requestException),
            factory: $this->factory
        );

        /** @Then HttpRequestInvalid is thrown */
        $this->expectException(HttpRequestInvalid::class);

        /** @When sending the request */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));
    }

    public function testGenericClientExceptionMapsToHttpRequestFailed(): void
    {
        /** @Given a PSR-18 client that throws a generic ClientExceptionInterface */
        $clientException = new class ('generic failure') extends RuntimeException implements ClientExceptionInterface {
        };

        $transport = NetworkTransport::with(
            client: $this->buildThrowingClient(exception: $clientException),
            factory: $this->factory
        );

        /** @Then HttpRequestFailed is thrown */
        $this->expectException(HttpRequestFailed::class);

        /** @When sending the request */
        $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));
    }

    public function testSuccessfulResponseIsWrappedInClientResponse(): void
    {
        /** @Given a client that returns a 200 response */
        $captured = null;
        $client = $this->buildCapturingClient(captured: $captured, statusCode: 200);
        $transport = NetworkTransport::with(
            client: $client,
            factory: $this->factory
        );

        /** @When sending a request */
        $response = $transport->send(request: Request::create(url: 'https://api.example.com/dragons'));

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testBodyWithInvalidUtf8IsSubstitutedAndRequestSendsNormally(): void
    {
        /** @Given a transport configured with a capturing client */
        $captured = null;
        $transport = NetworkTransport::with(
            client: $this->buildCapturingClient(captured: $captured, statusCode: 200),
            factory: $this->factory
        );

        /** @When sending a request whose body contains a non-UTF-8 byte sequence */
        $transport->send(
            request: Request::create(
                url: 'https://api.example.com/dragons',
                body: ['value' => "\xB0\xB1\xB2"],
                method: Method::POST
            )
        );

        /** @Then the PSR-7 request body carries the JSON-escaped replacement character and no exception is thrown */
        self::assertStringContainsString('\ufffd', (string)$captured->getBody());
    }

    private function buildCapturingClient(?RequestInterface &$captured, int $statusCode): ClientInterface
    {
        $response = $this->factory->createResponse($statusCode);

        return new class ($response, $captured) implements ClientInterface {
            public function __construct(
                private readonly ResponseInterface $response,
                private ?RequestInterface &$captured
            ) {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->captured = $request;

                return $this->response;
            }
        };
    }

    private function buildThrowingClient(Throwable $exception): ClientInterface
    {
        return new class ($exception) implements ClientInterface {
            public function __construct(private readonly Throwable $exception)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }
}
