<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

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

    public function testSendReturnsResponseWithCorrectCode(): void
    {
        /** @Given a transport seeded with a 200 response */
        $transport = $this->buildTransport(statusCode: 200);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending a valid request */
        $response = $http->send(request: Request::create(url: '/dragons'));

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testBaseUrlWithTrailingSlashAndPathWithLeadingSlashProducesNoDoubleSlash(): void
    {
        /** @Given a transport seeded with a 200 response and a base URL ending in slash */
        $transport = $this->buildTransport(statusCode: 200);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com/')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending a request whose path starts with a slash */
        $response = $http->send(request: Request::create(url: '/dragons'));

        /** @Then the response is returned without double slash in the URL */
        self::assertSame(Code::OK, $response->code());
    }

    public function testQueryParametersAreAppendedAsRfc3986(): void
    {
        /** @Given a transport seeded with a 200 response */
        $transport = $this->buildTransport(statusCode: 200);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending a request with query parameters */
        $response = $http->send(
            request: Request::create(url: '/dragons', query: ['sort' => 'name', 'order' => 'asc'])
        );

        /** @Then the response code is correct */
        self::assertSame(Code::OK, $response->code());
    }

    public function testRequestWithBodySendsJsonPayload(): void
    {
        /** @Given a transport seeded with a 201 response */
        $transport = $this->buildTransport(statusCode: 201);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending a request with a JSON body */
        $response = $http->send(
            request: Request::create(url: '/dragons', body: ['name' => 'Hydra'], method: Method::POST)
        );

        /** @Then the response code is correct */
        self::assertSame(Code::CREATED, $response->code());
    }

    public function testNetworkExceptionMapsToHttpNetworkFailed(): void
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
            client: $this->buildFailingClient(exception: $networkException),
            factory: $this->factory
        );

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @Then HttpNetworkFailed is thrown */
        $this->expectException(HttpNetworkFailed::class);

        /** @When sending the request */
        $http->send(request: Request::create(url: '/dragons'));
    }

    public function testRequestExceptionMapsToHttpRequestInvalid(): void
    {
        /** @Given a PSR-18 client that throws RequestExceptionInterface */
        $requestException = new class ('bad request') extends RuntimeException implements RequestExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return new Psr17Factory()->createRequest('GET', 'https://api.example.com');
            }
        };

        $transport = NetworkTransport::with(
            client: $this->buildFailingClient(exception: $requestException),
            factory: $this->factory
        );

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @Then HttpRequestInvalid is thrown */
        $this->expectException(HttpRequestInvalid::class);

        /** @When sending the request */
        $http->send(request: Request::create(url: '/dragons'));
    }

    public function testGenericClientExceptionMapsToHttpRequestFailed(): void
    {
        /** @Given a PSR-18 client that throws a generic ClientExceptionInterface */
        $clientException = new class ('generic failure') extends RuntimeException implements ClientExceptionInterface {
        };

        $transport = NetworkTransport::with(
            client: $this->buildFailingClient(exception: $clientException),
            factory: $this->factory
        );

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @Then HttpRequestFailed is thrown */
        $this->expectException(HttpRequestFailed::class);

        /** @When sending the request */
        $http->send(request: Request::create(url: '/dragons'));
    }

    public function testMalformedPathWithProtocolRelativeThrowsMalformedPath(): void
    {
        /** @Given an Http instance with a base URL */
        $transport = $this->buildTransport(statusCode: 200);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @Then MalformedPath is thrown without invoking the transport */
        $this->expectException(MalformedPath::class);

        /** @When sending a request whose path is protocol-relative */
        $http->send(request: Request::create(url: '//evil.example.com/attack'));
    }

    public function testMalformedPathWithSchemeThrowsMalformedPath(): void
    {
        /** @Given an Http instance with a base URL */
        $transport = $this->buildTransport(statusCode: 200);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @Then MalformedPath is thrown */
        $this->expectException(MalformedPath::class);

        /** @When sending a request whose path contains a scheme */
        $http->send(request: Request::create(url: 'javascript:alert(1)'));
    }

    public function testMalformedPathWithControlCharactersThrowsMalformedPath(): void
    {
        /** @Given an Http instance with a base URL */
        $transport = $this->buildTransport(statusCode: 200);

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @Then MalformedPath is thrown */
        $this->expectException(MalformedPath::class);

        /** @When sending a request whose path contains control characters */
        $http->send(request: Request::create(url: "/dragons\x00/evil"));
    }

    public function testNetworkExceptionPreservesPreviousChain(): void
    {
        /** @Given a network exception */
        $networkException = new class ('timeout') extends RuntimeException implements NetworkExceptionInterface {
            public function getRequest(): RequestInterface
            {
                return new Psr17Factory()->createRequest('GET', 'https://api.example.com');
            }
        };

        $transport = NetworkTransport::with(
            client: $this->buildFailingClient(exception: $networkException),
            factory: $this->factory
        );

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending the request */
        try {
            $http->send(request: Request::create(url: '/dragons'));
        } catch (HttpNetworkFailed $exception) {
            /** @Then the previous exception is preserved in the chain */
            self::assertSame($networkException, $exception->getPrevious());
        }
    }

    private function buildTransport(int $statusCode): NetworkTransport
    {
        $response = $this->factory->createResponse($statusCode);

        $client = new readonly class ($response) implements ClientInterface {
            public function __construct(private ResponseInterface $response)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        return NetworkTransport::with(
            client: $client,
            factory: $this->factory
        );
    }

    private function buildFailingClient(Throwable $exception): ClientInterface
    {
        return new readonly class ($exception) implements ClientInterface {
            public function __construct(private Throwable $exception)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }
}
