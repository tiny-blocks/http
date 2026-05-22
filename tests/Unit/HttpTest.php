<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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
        $response = $http->send(request: Request::get(url: '/dragons'));

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
        $response = $http->send(request: Request::get(url: '/dragons'));

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
            request: Request::get(url: '/dragons', queryParameters: ['sort' => 'name', 'order' => 'asc'])
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
            request: Request::post(url: '/dragons', body: ['name' => 'Hydra'])
        );

        /** @Then the response code is correct */
        self::assertSame(Code::CREATED, $response->code());
    }

    public function testSendWhenClientRaisesNetworkExceptionThenThrowsHttpNetworkFailed(): void
    {
        /** @Given a PSR-18 client that throws NetworkExceptionInterface */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: new PsrNetworkException('connection refused')),
                factory: $this->factory
            ))
            ->build();

        /** @Then HttpNetworkFailed is thrown */
        $this->expectException(HttpNetworkFailed::class);

        /** @When sending the request */
        $http->send(request: Request::get(url: '/dragons'));
    }

    public function testSendWhenClientRaisesRequestExceptionThenThrowsHttpRequestInvalid(): void
    {
        /** @Given a PSR-18 client that throws RequestExceptionInterface */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: new PsrRequestException('bad request')),
                factory: $this->factory
            ))
            ->build();

        /** @Then HttpRequestInvalid is thrown */
        $this->expectException(HttpRequestInvalid::class);

        /** @When sending the request */
        $http->send(request: Request::get(url: '/dragons'));
    }

    public function testSendWhenGenericClientExceptionRaisedThenThrowsHttpRequestFailed(): void
    {
        /** @Given a PSR-18 client that throws a generic ClientExceptionInterface */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: new PsrClientException('generic failure')),
                factory: $this->factory
            ))
            ->build();

        /** @Then HttpRequestFailed is thrown */
        $this->expectException(HttpRequestFailed::class);

        /** @When sending the request */
        $http->send(request: Request::get(url: '/dragons'));
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
        $http->send(request: Request::get(url: '//evil.example.com/attack'));
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
        $http->send(request: Request::get(url: 'javascript:alert(1)'));
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
        $http->send(request: Request::get(url: "/dragons\x00/evil"));
    }

    public function testSendWhenNetworkExceptionRaisedThenPreservesPreviousChain(): void
    {
        /** @Given a network exception */
        $networkException = new PsrNetworkException('timeout');

        /** @And an Http instance with a transport that throws it */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: ThrowingClient::throwing(exception: $networkException),
                factory: $this->factory
            ))
            ->build();

        /** @When sending the request */
        try {
            $http->send(request: Request::get(url: '/dragons'));
            self::fail('HttpNetworkFailed was expected.');
        } catch (HttpNetworkFailed $exception) {
            /** @Then the previous exception is preserved in the chain */
            self::assertSame($networkException, $exception->getPrevious());
        }
    }

    public function testSendWhenSchemePathGivenThenChainsPathContainsSchemeAsPrevious(): void
    {
        /** @Given an Http instance with a base URL */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @And a request whose path contains a scheme */
        $request = Request::get(url: 'https://attacker.com/steal');

        /** @When sending the request */
        try {
            $http->send(request: $request);
            self::fail('MalformedPath was expected.');
        } catch (MalformedPath $exception) {
            /** @Then the previous exception carries the offending path and a scheme-related reason */
            $previous = $exception->getPrevious();
            self::assertNotNull($previous);
            self::assertStringContainsString('https://attacker.com/steal', $previous->getMessage());
            self::assertStringContainsString('scheme', $previous->getMessage());
        }
    }

    public function testSendWhenSchemePathGivenThenMalformedPathExposesOffendingPath(): void
    {
        /** @Given an Http instance with a base URL */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @And a request whose path contains a scheme */
        $request = Request::get(url: 'https://attacker.com/steal');

        try {
            /** @When sending the request */
            $http->send(request: $request);
        } catch (MalformedPath $exception) {
            /** @Then the exception exposes the offending path */
            self::assertSame('https://attacker.com/steal', $exception->path());
        }
    }

    public function testSendWhenControlCharPathGivenThenChainsPathContainsControlCharsAsPrevious(): void
    {
        /** @Given an Http instance with a base URL */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(
                client: CapturingClient::returningStatus(statusCode: 200),
                factory: $this->factory
            ))
            ->build();

        /** @And a request whose path contains a control character */
        $request = Request::get(url: "/dragons\x00/evil");

        /** @When sending the request */
        try {
            $http->send(request: $request);
            self::fail('MalformedPath was expected.');
        } catch (MalformedPath $exception) {
            /** @Then the previous exception carries the offending path and a control-character reason */
            $previous = $exception->getPrevious();
            self::assertNotNull($previous);
            self::assertStringContainsString("/dragons\x00/evil", $previous->getMessage());
            self::assertStringContainsString('control characters', $previous->getMessage());
        }
    }

    public function testSendWhenBaseUrlEmptyAndRelativePathGivenThenUsesPathDirectly(): void
    {
        /** @Given an Http instance with an empty base URL */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $http = Http::with(baseUrl: '', transport: NetworkTransport::with(
            client: $client,
            factory: $this->factory
        ));

        /** @And a request with a relative path */
        $request = Request::get(url: '/dragons');

        /** @When sending the request */
        $http->send(request: $request);

        /** @Then the PSR-7 request URI is the path as-is */
        self::assertNotNull($client->captured);
        self::assertSame('/dragons', (string)$client->captured->getUri());
    }

    public function testSendWhenBaseUrlEndsWithSlashAndPathLeadsWithSlashThenSingleSlashJoinsThem(): void
    {
        /** @Given an Http instance with a trailing slash on the base URL */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $http = Http::with(baseUrl: 'https://api.example.com/', transport: NetworkTransport::with(
            client: $client,
            factory: $this->factory
        ));

        /** @And a request whose path starts with a slash */
        $request = Request::get(url: '/dragons');

        /** @When sending the request */
        $http->send(request: $request);

        /** @Then the composed URI joins them with exactly one slash */
        self::assertNotNull($client->captured);
        self::assertSame('https://api.example.com/dragons', (string)$client->captured->getUri());
    }

    public function testSendWhenBaseUrlWithoutTrailingSlashAndPathWithoutLeadingSlashThenJoinsWithSingleSlash(): void
    {
        /** @Given an Http instance without trailing slash on the base URL */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $http = Http::with(baseUrl: 'https://api.example.com', transport: NetworkTransport::with(
            client: $client,
            factory: $this->factory
        ));

        /** @And a request whose path lacks a leading slash */
        $request = Request::get(url: 'dragons');

        /** @When sending the request */
        $http->send(request: $request);

        /** @Then the composed URI joins them with exactly one slash */
        self::assertNotNull($client->captured);
        self::assertSame('https://api.example.com/dragons', (string)$client->captured->getUri());
    }

    public function testSendWhenQueryProvidedThenAppendsAsQueryString(): void
    {
        /** @Given an Http instance and a query payload */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $http = Http::with(baseUrl: 'https://api.example.com', transport: NetworkTransport::with(
            client: $client,
            factory: $this->factory
        ));

        /** @And a request with query parameters */
        $request = Request::get(url: '/dragons', queryParameters: ['sort' => 'name']);

        /** @When sending the request */
        $http->send(request: $request);

        /** @Then the composed URI includes the encoded query string */
        self::assertNotNull($client->captured);
        self::assertSame('https://api.example.com/dragons?sort=name', (string)$client->captured->getUri());
    }

    public function testSendWhenCustomTransportRaisesNetworkFailureThenExceptionCarriesRequestContext(): void
    {
        /** @Given a custom transport that wraps a non-PSR network error and re-raises via the documented factory */
        $http = Http::with(
            baseUrl: 'https://api.example.com',
            transport: FailingTransport::raisingNetworkFailure(
                reason: 'DNS resolution failed.',
                cause: new RuntimeException('curl: getaddrinfo')
            )
        );

        try {
            /** @When sending a request through the custom transport */
            $http->send(request: Request::head(url: '/dragons'));
            self::fail('HttpNetworkFailed was expected.');
        } catch (HttpNetworkFailed $exception) {
            /** @Then the exception carries the originating URL, method, and reason */
            self::assertSame('https://api.example.com/dragons', $exception->url());
            self::assertSame(Method::HEAD, $exception->method());
            self::assertSame('DNS resolution failed.', $exception->reason());
        }
    }

    public function testSendWhenCustomTransportRaisesRequestInvalidThenExceptionCarriesRequestContext(): void
    {
        /** @Given a custom transport that maps an upstream validation error to HttpRequestInvalid */
        $http = Http::with(
            baseUrl: 'https://api.example.com',
            transport: FailingTransport::raisingRequestInvalid(
                reason: 'Upstream validator rejected the payload.',
                cause: new RuntimeException('validator: required field missing')
            )
        );

        try {
            /** @When sending a request through the custom transport */
            $http->send(request: Request::patch(url: '/dragons'));
            self::fail('HttpRequestInvalid was expected.');
        } catch (HttpRequestInvalid $exception) {
            /** @Then the exception carries the originating URL, method, and reason */
            self::assertSame('https://api.example.com/dragons', $exception->url());
            self::assertSame(Method::PATCH, $exception->method());
            self::assertSame('Upstream validator rejected the payload.', $exception->reason());
        }
    }

    public function testSendWhenCustomTransportRaisesRequestFailureThenExceptionCarriesRequestContext(): void
    {
        /** @Given a custom transport that maps an upstream cURL error to HttpRequestFailed */
        $http = Http::with(
            baseUrl: 'https://api.example.com',
            transport: FailingTransport::raisingRequestFailure(
                reason: 'cURL handle exhausted retries.',
                cause: new RuntimeException('curl: too many retries')
            )
        );

        try {
            /** @When sending a request through the custom transport */
            $http->send(request: Request::put(url: '/dragons'));
            self::fail('HttpRequestFailed was expected.');
        } catch (HttpRequestFailed $exception) {
            /** @Then the exception carries the originating URL, method, and reason */
            self::assertSame('https://api.example.com/dragons', $exception->url());
            self::assertSame(Method::PUT, $exception->method());
            self::assertSame('cURL handle exhausted retries.', $exception->reason());
        }
    }

    public function testSendWhenEmptyQueryArrayGivenThenNoTrailingQuestionMark(): void
    {
        /** @Given an Http instance and an empty query array */
        $client = CapturingClient::returningStatus(statusCode: 200);
        $http = Http::with(baseUrl: 'https://api.example.com', transport: NetworkTransport::with(
            client: $client,
            factory: $this->factory
        ));

        /** @And a request with an empty query array */
        $request = Request::get(url: '/dragons', queryParameters: []);

        /** @When sending the request */
        $http->send(request: $request);

        /** @Then the composed URI has no trailing question mark */
        self::assertNotNull($client->captured);
        self::assertSame('https://api.example.com/dragons', (string)$client->captured->getUri());
    }

}
