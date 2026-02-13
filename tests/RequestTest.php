<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
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
            ->method('getBody')
            ->willReturn($stream);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the body of the HTTP Request */
        $actual = $request->decode()->body;

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
            ->method('getAttribute')
            ->with('__route__')
            ->willReturn([
                'name' => $routeName,
                'id'   => $attribute
            ]);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request */
        $actual = $request->decode()->uri->route()->get(key: 'id');

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
            ->method('getAttribute')
            ->with('__route__')
            ->willReturn([
                'name' => $routeName,
                ...$attributes
            ]);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request */
        $route = $request->decode()->uri->route();

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
            ->method('getAttribute')
            ->with('__route__')
            ->willReturn([
                'name' => '/v1/dragons/{id}',
                $key   => $value
            ]);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request and convert it to the expected type */
        $actual = $request->decode()->uri->route()->get(key: $key)->$method();

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
            ->method('getAttribute')
            ->with('__route__')
            ->willReturn($attribute);

        /** @When we create the HTTP Request with this ServerRequestInterface */
        $request = Request::from(request: $serverRequest);

        /** @And we decode the route attribute of the HTTP Request */
        $actual = $request->decode()->uri->route()->get(key: 'id');

        /** @Then the decoded attribute should match the original scalar value */
        self::assertSame($attribute, $actual->toString());
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
