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
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Http;
use TinyBlocks\Http\HttpBuilder;
use TinyBlocks\Http\Method;

final class HttpBuilderTest extends TestCase
{
    public function testCreateWhenInvokedThenReturnsEmptyBuilder(): void
    {
        /** @When calling Http::create() */
        $builder = Http::create();

        /** @Then a builder instance is returned */
        self::assertInstanceOf(HttpBuilder::class, $builder);
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

    public function testWithTransportWhenInvokedThenOriginalBuilderStillThrows(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @And a fresh transport */
        $transport = NetworkTransport::with(
            client: CapturingClient::returningStatus(statusCode: 200),
            factory: new Psr17Factory()
        );

        /** @And the original builder receives a new transport */
        $original->withTransport(transport: $transport);

        /** @Then the original builder still throws on build */
        $this->expectException(HttpConfigurationInvalid::class);

        /** @When calling build on the original builder */
        $original->build();
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
        $response = $http->send(request: Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        ));

        /** @Then the response is returned correctly */
        self::assertSame(Code::OK, $response->code());
    }

    public function testWithWhenInvokedDirectlyThenReturnsWorkingHttp(): void
    {
        /** @Given a transport seeded with one response */
        $transport = InMemoryTransport::with(responses: [Response::with(code: Code::OK)]);

        /** @When constructing Http directly via Http::with */
        $http = Http::with(baseUrl: 'https://api.example.com', transport: $transport);

        /** @And a simple GET request */
        $request = Request::create(
            url: '/dragons',
            body: null,
            query: null,
            method: Method::GET,
            headers: Headers::from()
        );

        /** @Then the instance can send requests and returns the correct response */
        self::assertSame(Code::OK, $http->send(request: $request)->code());
    }
}
