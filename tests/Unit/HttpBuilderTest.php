<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transports\InMemoryTransport;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;
use TinyBlocks\Http\Http;
use TinyBlocks\Http\HttpBuilder;

final class HttpBuilderTest extends TestCase
{
    public function testCreateReturnsEmptyBuilder(): void
    {
        /** @When calling Http::create() */
        $builder = Http::create();

        /** @Then a builder instance is returned */
        self::assertInstanceOf(HttpBuilder::class, $builder);
    }

    public function testWithTransportReturnNewBuilderAndOriginalIsUnchanged(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        $factory = new Psr17Factory();
        $transport = NetworkTransport::with(
            client: new class implements ClientInterface {
                public function sendRequest(RequestInterface $request): ResponseInterface
                {
                    return (new Psr17Factory())->createResponse(200);
                }
            },
            streamFactory: $factory,
            requestFactory: $factory
        );

        /** @When calling withTransport */
        $updated = $original->withTransport(transport: $transport);

        /** @Then a new builder instance is returned and original build still throws */
        self::assertNotSame($original, $updated);
        $this->expectException(HttpConfigurationInvalid::class);
        $original->build();
    }

    public function testWithBaseUrlReturnsNewBuilderAndOriginalIsUnchanged(): void
    {
        /** @Given an empty builder */
        $original = Http::create();

        /** @When calling withBaseUrl */
        $updated = $original->withBaseUrl(url: 'https://api.example.com');

        /** @Then a new builder instance is returned and original build still throws */
        self::assertNotSame($original, $updated);
        $this->expectException(HttpConfigurationInvalid::class);
        $original->build();
    }

    public function testBuildWithoutTransportThrowsHttpConfigurationInvalid(): void
    {
        /** @Given a builder with no transport */
        $builder = Http::create()->withBaseUrl(url: 'https://api.example.com');

        /** @Then HttpConfigurationInvalid is thrown */
        $this->expectException(HttpConfigurationInvalid::class);
        $this->expectExceptionMessage('Transport is required to build Http.');

        /** @When calling build */
        $builder->build();
    }

    public function testBuildWithoutBaseUrlThrowsHttpConfigurationInvalid(): void
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

    public function testFullyConfiguredBuilderProducesWorkingHttp(): void
    {
        /** @Given a fully configured builder */
        $transport = InMemoryTransport::with(
            responses: [Response::with(code: Code::OK)]
        );

        $http = Http::create()
            ->withBaseUrl(url: 'https://api.example.com')
            ->withTransport(transport: $transport)
            ->build();

        /** @When sending a request */
        $response = $http->send(request: Request::create(url: '/dragons'));

        /** @Then the response is returned correctly */
        self::assertSame(Code::OK, $response->code());
    }
}
