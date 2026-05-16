<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Server;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\Server\Request;

final class RequestTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testDecodeWhenBodyGivenThenExposesTypedAccessors(): void
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

        /** @And a real PSR-7 server request with that JSON body */
        $serverRequest = new ServerRequest(
            method: 'POST',
            uri: 'https://api.example.com/dragons',
            body: $this->factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION))
        );

        /** @When decoding the request body */
        $actual = Request::from(request: $serverRequest)->decode()->body();

        /** @Then every typed accessor matches the original payload */
        self::assertSame($payload, $actual->toArray());
        self::assertSame($payload['id'], $actual->get(key: 'id')->toInteger());
        self::assertSame($payload['name'], $actual->get(key: 'name')->toString());
        self::assertSame($payload['type'], $actual->get(key: 'type')->toString());
        self::assertSame($payload['weight'], $actual->get(key: 'weight')->toFloat());
        self::assertSame($payload['skills'], $actual->get(key: 'skills')->toArray());
        self::assertSame($payload['is_legendary'], $actual->get(key: 'is_legendary')->toBoolean());
    }

    public function testDecodeWhenRouteHasSingleAttributeThenExposesIt(): void
    {
        /** @Given a route attribute carrying a single id */
        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com/dragons/dragon-id')
            ->withAttribute('__route__', ['name' => '/v1/dragons/{id}', 'id' => 'dragon-id']);

        /** @When decoding the route attribute */
        $actual = Request::from(request: $serverRequest)->decode()->uri()->route()->get(key: 'id');

        /** @Then the value is returned as a string */
        self::assertSame('dragon-id', $actual->toString());
    }

    public function testDecodeWhenRouteHasMultipleAttributesThenExposesEach(): void
    {
        /** @Given a set of route attributes */
        $attributes = ['id' => 'dragon-id', 'skill' => 'dragon-skill', 'weight' => 6000.00];

        /** @And a server request carrying those attributes under the canonical route key */
        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com')
            ->withAttribute('__route__', ['name' => '/v1/dragons/{id}/skills/{skill}', ...$attributes]);

        /** @When decoding each attribute */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then each typed accessor matches */
        self::assertSame($attributes['id'], $route->get(key: 'id')->toString());
        self::assertSame($attributes['skill'], $route->get(key: 'skill')->toString());
        self::assertSame($attributes['weight'], $route->get(key: 'weight')->toFloat());
    }

    #[DataProvider('attributeConversionsProvider')]
    public function testDecodeWhenAttributeTypedConversionRequestedThenReturnsExpectedValue(
        string $key,
        mixed $value,
        string $method,
        mixed $expected
    ): void {
        /** @Given a route attribute with the provided value */
        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com')
            ->withAttribute('__route__', ['name' => '/v1/dragons/{id}', $key => $value]);

        /** @When converting through the typed accessor */
        $actual = Request::from(request: $serverRequest)->decode()->uri()->route()->get(key: $key)->$method();

        /** @Then the converted value matches the expected one */
        self::assertSame($expected, $actual);
    }

    public function testDecodeWhenRouteAttributeIsScalarThenExposesIt(): void
    {
        /** @Given a scalar route attribute value */
        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com')
            ->withAttribute('__route__', 'dragon-id');

        /** @When decoding the route attribute */
        $actual = Request::from(request: $serverRequest)->decode()->uri()->route()->get(key: 'id');

        /** @Then the value is returned */
        self::assertSame('dragon-id', $actual->toString());
    }

    public function testDecodeWhenSlimStyleRouteObjectGivenThenResolvesArguments(): void
    {
        /** @Given a Slim-style route object that stores params in getArguments() */
        $routeObject = new class {
            public function getArguments(): array
            {
                return ['id' => '42', 'email' => 'dragon@fire.com'];
            }
        };

        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com')
            ->withAttribute('__route__', $routeObject);

        /** @When decoding the route */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the params resolve from the object */
        self::assertSame('42', $route->get(key: 'id')->toString());
        self::assertSame(42, $route->get(key: 'id')->toInteger());
        self::assertSame('dragon@fire.com', $route->get(key: 'email')->toString());
    }

    public function testDecodeWhenMezzioStyleRouteResultGivenThenResolvesMatchedParams(): void
    {
        /** @Given a Mezzio-style route result object that uses getMatchedParams() */
        $routeResult = new class {
            public function getMatchedParams(): array
            {
                return ['id' => '99', 'slug' => 'fire-dragon'];
            }
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withAttribute('routeResult', $routeResult);

        /** @When decoding using the known-attribute scan */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the params resolve correctly */
        self::assertSame('99', $route->get(key: 'id')->toString());
        self::assertSame('fire-dragon', $route->get(key: 'slug')->toString());
    }

    public function testDecodeWhenSymfonyStyleRouteParamsGivenThenResolvesWithExplicitName(): void
    {
        /** @Given Symfony stores route params under _route_params */
        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withAttribute('_route_params', ['id' => '7', 'category' => 'legendary']);

        /** @When decoding with the custom route attribute name */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route(name: '_route_params');

        /** @Then the params resolve correctly */
        self::assertSame('7', $route->get(key: 'id')->toString());
        self::assertSame('legendary', $route->get(key: 'category')->toString());
    }

    public function testDecodeWhenSymfonyAttributePresentThenFallbackScanFindsIt(): void
    {
        /** @Given Symfony stores params under _route_params and default __route__ is absent */
        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withAttribute('_route_params', ['id' => '55']);

        /** @When decoding with the default route() */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the fallback scan finds params under _route_params */
        self::assertSame('55', $route->get(key: 'id')->toString());
    }

    public function testDecodeWhenDirectAttributesPresentThenFallbackResolves(): void
    {
        /** @Given a request that stores route params as direct attributes */
        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withAttribute('id', '123')
            ->withAttribute('email', 'user@example.com');

        /** @When decoding with the default route() */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then direct attributes resolve as fallback */
        self::assertSame('123', $route->get(key: 'id')->toString());
        self::assertSame('user@example.com', $route->get(key: 'email')->toString());
    }

    public function testDecodeWhenManualAttributesInjectedThenExposesValues(): void
    {
        /** @Given a request manually injecting route params via withAttribute() */
        $serverRequest = (new ServerRequest(method: 'POST', uri: 'https://api.example.com'))
            ->withAttribute('__route__', ['id' => 'manually-injected', 'status' => 'active']);

        /** @When decoding */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then the injected values are returned */
        self::assertSame('manually-injected', $route->get(key: 'id')->toString());
        self::assertSame('active', $route->get(key: 'status')->toString());
    }

    public function testDecodeWhenRouteObjectExposesPublicPropertyThenResolvesIt(): void
    {
        /** @Given a route object exposing public properties */
        $routeObject = new class {
            public array $arguments = ['id' => '10', 'name' => 'Hydra'];
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withAttribute('__route__', $routeObject);

        /** @When decoding */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then public property values resolve */
        self::assertSame('10', $route->get(key: 'id')->toString());
        self::assertSame('Hydra', $route->get(key: 'name')->toString());
    }

    public function testDecodeWhenRouteObjectExposesNonArrayMethodAndPropertyThenFallsBackToEmpty(): void
    {
        /** @Given a route object whose matching method and property both return non-array values */
        $routeObject = new class {
            public string $arguments = 'not-an-array';

            public function getArguments(): string
            {
                return 'not-an-array';
            }
        };

        /** @And a server request carrying that object under the canonical route key */
        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withAttribute('__route__', $routeObject);

        /** @When decoding any route attribute */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then safe defaults are returned because no array could be extracted */
        self::assertSame('', $route->get(key: 'id')->toString());
    }

    public function testDecodeWhenNoRouteAttributesGivenThenSafeDefaultsAreReturned(): void
    {
        /** @Given a server request with no route attributes at all */
        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com');

        /** @When decoding any route attribute */
        $route = Request::from(request: $serverRequest)->decode()->uri()->route();

        /** @Then safe defaults are returned */
        self::assertSame(0, $route->get(key: 'id')->toInteger());
        self::assertSame('', $route->get(key: 'name')->toString());
        self::assertSame(0.00, $route->get(key: 'weight')->toFloat());
        self::assertFalse($route->get(key: 'active')->toBoolean());
        self::assertSame([], $route->get(key: 'tags')->toArray());
    }

    public function testDecodeWhenParsedBodyPresentAndStreamEmptyThenUsesParsedBody(): void
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

        $serverRequest = (new ServerRequest(method: 'POST', uri: 'https://api.example.com'))
            ->withBody($this->factory->createStream(''))
            ->withParsedBody($payload);

        /** @When decoding the body */
        $actual = Request::from(request: $serverRequest)->decode()->body();

        /** @Then the parsed body is exposed */
        self::assertSame($payload, $actual->toArray());
        self::assertSame($payload['id'], $actual->get(key: 'id')->toInteger());
        self::assertSame($payload['weight'], $actual->get(key: 'weight')->toFloat());
        self::assertSame($payload['is_legendary'], $actual->get(key: 'is_legendary')->toBoolean());
    }

    public function testDecodeWhenUriGivenThenExposesAsString(): void
    {
        /** @Given a full URI on the server request */
        $expectedUri = 'https://api.example.com/v1/dragons?sort=name&order=asc';
        $serverRequest = new ServerRequest(method: 'GET', uri: $expectedUri);

        /** @When decoding the URI */
        $actual = Request::from(request: $serverRequest)->decode()->uri()->toString();

        /** @Then the URI string matches */
        self::assertSame($expectedUri, $actual);
    }

    public function testDecodeWhenQueryParamsPresentThenExposesTypedAccessors(): void
    {
        /** @Given query parameters present on the request URI */
        $queryParams = ['sort' => 'name', 'order' => 'asc', 'limit' => '50', 'active' => 'true'];

        $serverRequest = (new ServerRequest(method: 'GET', uri: 'https://api.example.com'))
            ->withQueryParams($queryParams);

        /** @When decoding the query parameters */
        $actual = Request::from(request: $serverRequest)->decode()->uri()->queryParameters();

        /** @Then every accessor matches */
        self::assertSame($queryParams, $actual->toArray());
        self::assertSame($queryParams['sort'], $actual->get(key: 'sort')->toString());
        self::assertSame($queryParams['order'], $actual->get(key: 'order')->toString());
        self::assertSame(50, $actual->get(key: 'limit')->toInteger());
        self::assertTrue($actual->get(key: 'active')->toBoolean());
    }

    public function testDecodeWhenQueryParamsAbsentThenSafeDefaultsReturned(): void
    {
        /** @Given a server request with no query parameters */
        $serverRequest = new ServerRequest(method: 'GET', uri: 'https://api.example.com');

        /** @When decoding the query parameters */
        $actual = Request::from(request: $serverRequest)->decode()->uri()->queryParameters();

        /** @Then safe defaults are returned */
        self::assertSame([], $actual->toArray());
        self::assertSame('', $actual->get(key: 'sort')->toString());
        self::assertSame(0, $actual->get(key: 'page')->toInteger());
        self::assertSame(0.00, $actual->get(key: 'price')->toFloat());
        self::assertFalse($actual->get(key: 'active')->toBoolean());
    }

    public function testMethodWhenPostRequestGivenThenReturnsPostEnum(): void
    {
        /** @Given a POST server request */
        $serverRequest = new ServerRequest(method: 'POST', uri: 'https://api.example.com');

        /** @When asking for the typed method */
        $actual = Request::from(request: $serverRequest)->method();

        /** @Then the Method enum is returned */
        self::assertSame(Method::POST, $actual);
    }

    #[DataProvider('httpMethodsProvider')]
    public function testMethodWhenAnyHttpVerbGivenThenReturnsMatchingEnum(
        string $methodString,
        Method $expectedMethod
    ): void {
        /** @Given a server request with the specified HTTP verb */
        $serverRequest = new ServerRequest(method: $methodString, uri: 'https://api.example.com');

        /** @When asking for the typed method */
        $actual = Request::from(request: $serverRequest)->method();

        /** @Then the Method enum matches */
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

    public function testDecodeWhenInvalidJsonBodyGivenThenReturnsEmptyArray(): void
    {
        /** @Given a non-JSON body */
        $serverRequest = (new ServerRequest(method: 'POST', uri: 'https://api.example.com'))
            ->withBody($this->factory->createStream('{not valid json]'));

        /** @When decoding */
        $decoded = Request::from(request: $serverRequest)->decode();

        /** @Then the body gracefully returns an empty array */
        self::assertSame([], $decoded->body()->toArray());
    }

    public function testDecodeWhenStreamAdvancedThenStillParsesFromStart(): void
    {
        /** @Given a seekable stream advanced past its start */
        $stream = $this->factory->createStream('{"name":"Hydra"}');
        $stream->getContents();

        /** @And a server request using that stream */
        $serverRequest = new ServerRequest(method: 'POST', uri: 'https://api.example.com')
            ->withBody($stream);

        /** @When decoding the request body */
        $decoded = Request::from(request: $serverRequest)->decode()->body();

        /** @Then the body parses correctly despite the stream position */
        self::assertSame('Hydra', $decoded->get(key: 'name')->toString());

        /** @And the stream is rewound so it can be re-read */
        self::assertSame('{"name":"Hydra"}', $stream->getContents());
    }

    public function testDecodeWhenEmptyStreamAndNonArrayParsedBodyThenReturnsEmpty(): void
    {
        /** @Given an empty stream and a non-array parsed body */
        $serverRequest = new ServerRequest(method: 'POST', uri: 'https://api.example.com')
            ->withBody($this->factory->createStream(''))
            ->withParsedBody(null);

        /** @When decoding */
        $decoded = Request::from(request: $serverRequest)->decode();

        /** @Then the body gracefully returns an empty array */
        self::assertSame([], $decoded->body()->toArray());
    }
}
