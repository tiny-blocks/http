<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transports\InMemoryTransport;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\BaseUrlIsInvalid;
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Http;
use TinyBlocks\Http\HttpBuilder;

final class HttpBuilderTest extends TestCase
{
    public function testWithBaseUrlWhenHttpGivenThenAccepts(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @When setting a valid http:// base URL */
        $updated = $builder->withBaseUrl(url: 'http://localhost:8080');

        /** @Then a new builder instance is returned without throwing */
        self::assertNotSame($builder, $updated);
    }

    public function testWithBaseUrlWhenHttpsGivenThenAccepts(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @When setting a valid https:// base URL */
        $updated = $builder->withBaseUrl(url: 'https://api.example.com');

        /** @Then a new builder instance is returned without throwing */
        self::assertNotSame($builder, $updated);
    }

    public function testCreateWhenInvokedThenReturnsEmptyBuilder(): void
    {
        /** @When calling Http::create() */
        $builder = Http::create();

        /** @Then a builder instance is returned */
        self::assertInstanceOf(HttpBuilder::class, $builder);
    }

    public function testWithBaseUrlWhenEmptyStringGivenThenAccepts(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @When setting an empty base URL */
        $updated = $builder->withBaseUrl(url: '');

        /** @Then a new builder instance is returned without throwing */
        self::assertNotSame($builder, $updated);
    }

    public function testWithBaseUrlWhenInvokedThenReturnsNewBuilder(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @When calling withBaseUrl */
        $updated = $original->withBaseUrl(url: 'https://api.example.com');

        /** @Then a new builder instance is returned */
        self::assertNotSame($original, $updated);
    }

    public function testWithBaseUrlWhenUppercaseHttpsGivenThenAccepts(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @When setting a base URL with uppercase scheme */
        $updated = $builder->withBaseUrl(url: 'HTTPS://api.example.com');

        /** @Then a new builder instance is returned without throwing */
        self::assertNotSame($builder, $updated);
    }

    public function testWithTransportWhenInvokedThenReturnsNewBuilder(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @And a fresh transport */
        $transport = NetworkTransport::with(
            client: CapturingClient::returningStatus(statusCode: 200),
            factory: new Psr17Factory()
        );

        /** @When calling withTransport */
        $updated = $original->withTransport(transport: $transport);

        /** @Then a new builder instance is returned */
        self::assertNotSame($original, $updated);
    }

    public function testWithWhenInvokedDirectlyThenReturnsWorkingHttp(): void
    {
        /** @Given a transport seeded with one response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::OK)]);

        /** @When constructing Http directly via Http::with */
        $http = Http::with(baseUrl: 'https://api.example.com', transport: $transport);

        /** @And a simple GET request */
        $request = Request::get(url: '/dragons');

        /** @Then the instance can send requests and returns the correct response */
        self::assertSame(Code::OK, $http->send(request: $request)->code());
    }

    public function testBuildWhenFullyConfiguredThenProducesWorkingHttp(): void
    {
        /** @Given a transport seeded with one response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::OK)]);

        /** @And a fully configured builder */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending a request */
        $response = $http->send(request: Request::get(url: '/dragons'));

        /** @Then the response is returned correctly */
        self::assertSame(Code::OK, $response->code());
    }

    public function testWithBaseUrlWhenInvokedThenOriginalBuilderStillThrows(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @And the original builder receives a new base URL */
        $original->withBaseUrl(url: 'https://api.example.com');

        /** @Then the original builder still throws on build */
        $this->expectException(HttpConfigurationInvalid::class);

        /** @When calling build on the original builder */
        $original->build();
    }

    public function testWithTransportWhenInvokedThenOriginalBuilderStillThrows(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @And a fresh transport */
        $transport = NetworkTransport::with(
            client: CapturingClient::returningStatus(statusCode: 200),
            factory: new Psr17Factory()
        );

        /** @And a copy derived by adding the transport to the original */
        $configured = $original->withTransport(transport: $transport);

        /** @Then the derived copy is a separate instance from the original */
        self::assertNotSame($original, $configured);

        /** @And the original builder still throws on build */
        $this->expectException(HttpConfigurationInvalid::class);

        /** @When calling build on the original builder */
        $original->build();
    }

    public function testWithBaseUrlWhenFtpSchemeGivenThenThrowsBaseUrlIsInvalid(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @Then an exception indicating the base URL is invalid is thrown */
        $this->expectException(BaseUrlIsInvalid::class);

        /** @When setting an ftp:// base URL */
        $builder->withBaseUrl(url: 'ftp://example.com');
    }

    public function testBuildWhenBaseUrlMissingThenThrowsHttpConfigurationInvalid(): void
    {
        /** @Given a builder with no base URL */
        $builder = Http::create()->withTransport(
            transport: InMemoryTransport::with(responses: [])
        );

        /** @Then HttpConfigurationInvalid is thrown */
        $this->expectException(HttpConfigurationInvalid::class);
        $this->expectExceptionMessage('Base URL is required to build Http.');

        /** @When calling build */
        $builder->build();
    }

    public function testWithBaseUrlWhenControlCharGivenThenThrowsBaseUrlIsInvalid(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @Then an exception indicating the base URL is invalid is thrown */
        $this->expectException(BaseUrlIsInvalid::class);

        /** @When setting a base URL containing a control character */
        $builder->withBaseUrl(url: "https://api.example.com\x00");
    }

    public function testBuildWhenTransportMissingThenThrowsHttpConfigurationInvalid(): void
    {
        /** @Given a builder with no transport */
        $builder = Http::create()->withBaseUrl(url: 'https://api.example.com');

        /** @Then HttpConfigurationInvalid is thrown */
        $this->expectException(HttpConfigurationInvalid::class);
        $this->expectExceptionMessage('Transport is required to build Http.');

        /** @When calling build */
        $builder->build();
    }

    public function testWithBaseUrlWhenJavascriptSchemeGivenThenThrowsBaseUrlIsInvalid(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @Then an exception indicating the base URL is invalid is thrown */
        $this->expectException(BaseUrlIsInvalid::class);
        $this->expectExceptionMessage('Base URL <javascript:alert(1)> is invalid');

        /** @When setting a javascript: scheme base URL */
        $builder->withBaseUrl(url: 'javascript:alert(1)');
    }

    public function testWithBaseUrlWhenProtocolRelativeGivenThenThrowsBaseUrlIsInvalid(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @Then an exception indicating the base URL is invalid is thrown */
        $this->expectException(BaseUrlIsInvalid::class);

        /** @When setting a protocol-relative base URL */
        $builder->withBaseUrl(url: '//host');
    }

    public function testWithBaseUrlWhenSchemeEmbeddedInPathGivenThenThrowsBaseUrlIsInvalid(): void
    {
        /** @Given an empty builder */
        $builder = Http::create();

        /** @Then an exception indicating the base URL is invalid is thrown */
        $this->expectException(BaseUrlIsInvalid::class);

        /** @When setting a base URL with the scheme embedded mid-string */
        $builder->withBaseUrl(url: 'example.com?redirect=https://api.example.com');
    }

    public function testWithDefaultHeadersWhenInvokedThenReturnsNewBuilder(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @When calling withDefaultHeaders */
        $updated = $original->withDefaultHeaders(
            headers: Headers::fromArray(entries: ['Authorization' => 'Bearer token'])
        );

        /** @Then a new builder instance is returned */
        self::assertNotSame($original, $updated);
    }

    public function testBuildWhenDefaultHeadersProvidedThenReachTransport(): void
    {
        /** @Given a capturing client */
        $client = CapturingClient::returningStatus(statusCode: 200);

        /** @And a builder configured with a default header */
        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: NetworkTransport::with(client: $client, factory: new Psr17Factory()))
            ->withDefaultHeaders(headers: Headers::fromArray(entries: ['Authorization' => 'Bearer token']))
            ->build();

        /** @When sending a request */
        $http->send(request: Request::get(url: '/dragons'));

        /** @Then the default header reaches the transport */
        self::assertNotNull($client->captured);
        self::assertSame('Bearer token', $client->captured->getHeaderLine('Authorization'));
    }
}
