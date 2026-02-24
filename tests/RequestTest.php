<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\Request;

final class RequestTest extends TestCase
{
    public function testRequestDecodingWithPayload(): void
    {
        /** @Given a payload to send */
        $payload = [
            'id'           => PHP_INT_MAX,
            'name'         => 'Drakengard Firestorm',
            'type'         => 'Dragon',
            'weight'       => 6000.00,
            'skills'       => ['Fire Breath', 'Flight', 'Regeneration'],
            'is_legendary' => true
        ];

        /** @And this payload is used to create a ServerRequestInterface */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->method('getContents')
            ->willReturn(json_encode($payload, JSON_PRESERVE_ZERO_FRACTION));

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('POST');
        $serverRequest
            ->method('getBody')
            ->willReturn($stream);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the body of the HTTP Request */
        $actual = $request->decode()->body();

        /** @Then the decoded body should match the original payload */
        self::assertSame($payload, $actual->toArray());
        self::assertSame($payload['id'], $actual->get(key: 'id')->toInteger());
        self::assertSame($payload['name'], $actual->get(key: 'name')->toString());
        self::assertSame($payload['type'], $actual->get(key: 'type')->toString());
        self::assertSame($payload['weight'], $actual->get(key: 'weight')->toFloat());
        self::assertSame($payload['skills'], $actual->get(key: 'skills')->toArray());
        self::assertSame($payload['is_legendary'], $actual->get(key: 'is_legendary')->toBoolean());
    }

    public function testRequestDecodingWithRouteWithSingleAttribute(): void
    {
        /** @Given a route name to be retrieved */
        $routeName = '/v1/dragons/{id}';

        /** @And an id to be retrieved from the route attribute */
        $attribute = 'dragon-id';

        /** @And a ServerRequestInterface with this route attribute */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => ['name' => $routeName, 'id' => $attribute],
                default     => null
            });

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request */
        $actual = $request->decode()->uri()->route()->get(key: 'id');

        self::assertSame($attribute, $actual->toString());
    }

    public function testRequestDecodingWithRouteWithMultipleAttributes(): void
    {
        /** @Given a route name to be retrieved */
        $routeName = '/v1/dragons/{id}/skills/{skill}';

        /** @And an id and skill to be retrieved from the route attribute */
        $attributes = [
            'id'     => 'dragon-id',
            'skill'  => 'dragon-skill',
            'weight' => 6000.00
        ];

        /** @And a ServerRequestInterface with this route attribute */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => ['name' => $routeName, ...$attributes],
                default     => null
            });

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request */
        $route = $request->decode()->uri()->route();

        self::assertSame($attributes['id'], $route->get(key: 'id')->toString());
        self::assertSame($attributes['skill'], $route->get(key: 'skill')->toString());
        self::assertSame($attributes['weight'], $route->get(key: 'weight')->toFloat());
    }

    #[DataProvider('attributeConversionsProvider')]
    public function testRequestWhenAttributeConversions(
        string $key,
        mixed $value,
        string $method,
        mixed $expected
    ): void {
        /** @Given a ServerRequestInterface with a route attribute */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => ['name' => '/v1/dragons/{id}', $key => $value],
                default     => null
            });

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request and convert it to the expected type */
        $actual = $request->decode()->uri()->route()->get(key: $key)->$method();

        /** @Then the converted value should match the expected value */
        self::assertSame($expected, $actual);
    }

    public function testRequestDecodingWithRouteAttributeAsScalar(): void
    {
        /** @Given a scalar route attribute value */
        $attribute = 'dragon-id';

        /** @And a ServerRequestInterface with this route attribute as scalar */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $attribute,
                default     => null
            });

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request */
        $actual = $request->decode()->uri()->route()->get(key: 'id');

        /** @Then the decoded attribute should match the original scalar value */
        self::assertSame($attribute, $actual->toString());
    }

    public function testRequestDecodingWithSlimStyleRouteObject(): void
    {
        /** @Given a Slim-style route object that stores params in getArguments() */
        $routeObject = new class {
            public function getArguments(): array
            {
                return ['id' => '42', 'email' => 'dragon@fire.com'];
            }
        };

        /** @And a ServerRequestInterface with this route object under __route__ */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $routeObject,
                default     => null
            });

        /** @When we create the HTTP Request and decode route params */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the params should be correctly resolved from the object */
        self::assertSame('42', $route->get(key: 'id')->toString());
        self::assertSame(42, $route->get(key: 'id')->toInteger());
        self::assertSame('dragon@fire.com', $route->get(key: 'email')->toString());
    }

    public function testRequestDecodingWithMezzioStyleRouteResult(): void
    {
        /** @Given a Mezzio-style route result object that uses getMatchedParams() */
        $routeResult = new class {
            /** @noinspection PhpUnused */
            public function getMatchedParams(): array
            {
                return ['id' => '99', 'slug' => 'fire-dragon'];
            }
        };

        /** @And a ServerRequestInterface with this route result under routeResult */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                'routeResult' => $routeResult,
                default       => null
            });

        /** @When we create the HTTP Request and decode using known attribute scan */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the params should be correctly resolved from the Mezzio object */
        self::assertSame('99', $route->get(key: 'id')->toString());
        self::assertSame('fire-dragon', $route->get(key: 'slug')->toString());
    }

    public function testRequestDecodingWithSymfonyStyleRouteParams(): void
    {
        /** @Given Symfony stores route params as an array under _route_params */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '_route_params' => ['id' => '7', 'category' => 'legendary'],
                default         => null
            });

        /** @When we use the custom route attribute name */
        $route = Request::from(request: $serverRequest)
            ->decode()
            ->uri()
            ->route(name: '_route_params');

        /** @Then the params should be correctly resolved */
        self::assertSame('7', $route->get(key: 'id')->toString());
        self::assertSame('legendary', $route->get(key: 'category')->toString());
    }

    public function testRequestDecodingWithSymfonyStyleFallbackScan(): void
    {
        /** @Given Symfony stores route params under _route_params and default __route__ is null */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '_route_params' => ['id' => '55'],
                default         => null
            });

        /** @When we use the default route() without specifying a name */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the fallback scan should find params under _route_params */
        self::assertSame('55', $route->get(key: 'id')->toString());
    }

    public function testRequestDecodingWithDirectAttributes(): void
    {
        /** @Given a framework like Laravel stores route params as direct request attributes */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                'id'    => '123',
                'email' => 'user@example.com',
                default => null
            });

        /** @When we decode route params using the default route */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then direct attributes should be resolved as fallback */
        self::assertSame('123', $route->get(key: 'id')->toString());
        self::assertSame('user@example.com', $route->get(key: 'email')->toString());
    }

    public function testRequestDecodingWithManualWithAttribute(): void
    {
        /** @Given a user manually injects route params via withAttribute() */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('POST');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => ['id' => 'manually-injected', 'status' => 'active'],
                default     => null
            });

        /** @When we decode route params */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the manually injected values should be returned */
        self::assertSame('manually-injected', $route->get(key: 'id')->toString());
        self::assertSame('active', $route->get(key: 'status')->toString());
    }

    public function testRequestDecodingWithObjectHavingPublicProperty(): void
    {
        /** @Given an object that exposes route params via a public property */
        $routeObject = new class {
            public array $arguments = ['id' => '10', 'name' => 'Hydra'];
        };

        /** @And a ServerRequestInterface with this object under __route__ */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $routeObject,
                default     => null
            });

        /** @When we decode route params */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then public property values should be resolved */
        self::assertSame('10', $route->get(key: 'id')->toString());
        self::assertSame('Hydra', $route->get(key: 'name')->toString());
    }

    public function testRequestDecodingReturnsDefaultsWhenNoRouteParams(): void
    {
        /** @Given a ServerRequestInterface with no route attributes at all */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getAttribute')
            ->willReturn(null);

        /** @When we try to decode route params */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then safe defaults should be returned */
        self::assertSame(0, $route->get(key: 'id')->toInteger());
        self::assertSame('', $route->get(key: 'name')->toString());
        self::assertSame(0.00, $route->get(key: 'weight')->toFloat());
        self::assertFalse($route->get(key: 'active')->toBoolean());
        self::assertSame([], $route->get(key: 'tags')->toArray());
    }

    public function testRequestDecodingWithParsedBody(): void
    {
        /** @Given a payload already parsed by the framework */
        $payload = [
            'id'           => PHP_INT_MAX,
            'name'         => 'Drakengard Firestorm',
            'type'         => 'Dragon',
            'weight'       => 6000.00,
            'skills'       => ['Fire Breath', 'Flight', 'Regeneration'],
            'is_legendary' => true
        ];

        /** @And a ServerRequestInterface with an empty stream but a parsed body */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->method('getContents')
            ->willReturn('');

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('POST');
        $serverRequest
            ->method('getBody')
            ->willReturn($stream);
        $serverRequest
            ->method('getParsedBody')
            ->willReturn($payload);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the body of the HTTP Request */
        $actual = $request->decode()->body();

        /** @Then the decoded body should match the parsed payload */
        self::assertSame($payload, $actual->toArray());
        self::assertSame($payload['id'], $actual->get(key: 'id')->toInteger());
        self::assertSame($payload['name'], $actual->get(key: 'name')->toString());
        self::assertSame($payload['type'], $actual->get(key: 'type')->toString());
        self::assertSame($payload['weight'], $actual->get(key: 'weight')->toFloat());
        self::assertSame($payload['skills'], $actual->get(key: 'skills')->toArray());
        self::assertSame($payload['is_legendary'], $actual->get(key: 'is_legendary')->toBoolean());
    }

    public function testRequestDecodingWithFullUri(): void
    {
        /** @Given a full URI string */
        $expectedUri = 'https://api.example.com/v1/dragons?sort=name&order=asc';

        /** @And a PSR-7 UriInterface mock that returns this URI */
        $uri = $this->createMock(UriInterface::class);
        $uri
            ->method('__toString')
            ->willReturn($expectedUri);

        /** @And a ServerRequestInterface that returns this UriInterface */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getUri')
            ->willReturn($uri);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we retrieve the full URI string from the decoded request */
        $actual = $request->decode()->uri()->toString();

        /** @Then the URI string should match the expected full URI */
        self::assertSame($expectedUri, $actual);
    }

    public function testRequestDecodingWithQueryParameters(): void
    {
        /** @Given query parameters present in the request URI */
        $queryParams = [
            'sort'   => 'name',
            'order'  => 'asc',
            'limit'  => '50',
            'active' => 'true'
        ];

        /** @And a ServerRequestInterface that returns these query parameters */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->method('getContents')
            ->willReturn('');

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getQueryParams')
            ->willReturn($queryParams);
        $serverRequest
            ->method('getBody')
            ->willReturn($stream);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we retrieve the query parameters from the decoded request */
        $actual = $request->decode()->uri()->queryParameters();

        /** @Then the query parameters should match the original values */
        self::assertSame($queryParams, $actual->toArray());
        self::assertSame($queryParams['sort'], $actual->get(key: 'sort')->toString());
        self::assertSame($queryParams['order'], $actual->get(key: 'order')->toString());
        self::assertSame(50, $actual->get(key: 'limit')->toInteger());
        self::assertTrue($actual->get(key: 'active')->toBoolean());
    }

    public function testRequestDecodingWithQueryParametersReturnsDefaultsWhenEmpty(): void
    {
        /** @Given a ServerRequestInterface with no query parameters */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->method('getContents')
            ->willReturn('');

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('GET');
        $serverRequest
            ->method('getQueryParams')
            ->willReturn([]);
        $serverRequest
            ->method('getBody')
            ->willReturn($stream);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we try to access query parameters that do not exist */
        $actual = $request->decode()->uri()->queryParameters();

        /** @Then safe defaults should be returned */
        self::assertSame([], $actual->toArray());
        self::assertSame('', $actual->get(key: 'sort')->toString());
        self::assertSame(0, $actual->get(key: 'page')->toInteger());
        self::assertSame(0.00, $actual->get(key: 'price')->toFloat());
        self::assertFalse($actual->get(key: 'active')->toBoolean());
    }

    public function testRequestWithMethod(): void
    {
        /** @Given a ServerRequestInterface with POST method */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn('POST');

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we retrieve the HTTP method */
        $actual = $request->method();

        /** @Then the method should match the expected enum value */
        self::assertSame(Method::POST, $actual);
        self::assertSame('POST', $actual->value);
    }

    #[DataProvider('httpMethodsProvider')]
    public function testRequestWithDifferentHttpMethods(string $methodString, Method $expectedMethod): void
    {
        /** @Given a ServerRequestInterface with the specified HTTP method */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getMethod')
            ->willReturn($methodString);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we retrieve the HTTP method */
        $actual = $request->method();

        /** @Then the method should match the expected enum value */
        self::assertSame($expectedMethod, $actual);
        self::assertSame($methodString, $actual->value);
    }

    public static function httpMethodsProvider(): array
    {
        return [
            'GET method'     => ['GET', Method::GET],
            'PUT method'     => ['PUT', Method::PUT],
            'POST method'    => ['POST', Method::POST],
            'HEAD method'    => ['HEAD', Method::HEAD],
            'PATCH method'   => ['PATCH', Method::PATCH],
            'TRACE method'   => ['TRACE', Method::TRACE],
            'DELETE method'  => ['DELETE', Method::DELETE],
            'OPTIONS method' => ['OPTIONS', Method::OPTIONS],
            'CONNECT method' => ['CONNECT', Method::CONNECT]
        ];
    }

    public static function attributeConversionsProvider(): array
    {
        return [
            'Float attribute conversion toString'                         => ['weight', 6000.00, 'toString', '6000'],
            'Float attribute conversion toInteger'                        => ['weight', 6000.00, 'toInteger', 6000],
            'Float attribute conversion toBoolean'                        => ['weight', 6000.00, 'toBoolean', true],
            'String attribute conversion toArray'                         => [
                'skills',
                '["Fire Breath", "Flight", "Regeneration"]',
                'toArray',
                []
            ],
            'String attribute conversion toFloat'                         => ['weight', '6000.00', 'toFloat', 6000.00],
            'String attribute conversion toInteger'                       => ['id', '123', 'toInteger', 123],
            'String attribute conversion toBoolean'                       => [
                'is_legendary',
                'true',
                'toBoolean',
                true
            ],
            'Integer attribute conversion toString'                       => ['id', 123, 'toString', '123'],
            'Integer attribute conversion toFloat'                        => ['id', 123, 'toFloat', 123.0],
            'Integer attribute conversion toBoolean'                      => ['id', 123, 'toBoolean', true],
            'Boolean attribute conversion toString'                       => ['is_legendary', true, 'toString', '1'],
            'Boolean attribute conversion toInteger'                      => ['is_legendary', true, 'toInteger', 1],
            'Boolean attribute conversion toFloat'                        => ['is_legendary', true, 'toFloat', 1.0],
            'Non-scalar attribute conversion toFloat defaults to 0.00'    => ['meta', ['x' => 1], 'toFloat', 0.00],
            'Non-scalar attribute conversion toInteger defaults to 0'     => ['meta', ['x' => 1], 'toInteger', 0],
            'Non-scalar attribute conversion toString defaults to empty'  => ['meta', ['x' => 1], 'toString', ''],
            'Non-scalar attribute conversion toBoolean defaults to false' => ['meta', ['x' => 1], 'toBoolean', false]
        ];
    }
}
