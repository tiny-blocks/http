<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Internal\Server\Request;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Server\Request\RouteParameterResolver;

final class RouteParameterResolverTest extends TestCase
{
    public function testResolveWhenArrayAttributeGivenThenReturnsItDirectly(): void
    {
        /** @Given a request with an array attribute under __route__ */
        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('__route__', ['id' => '42', 'slug' => 'test']);

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then the array is returned directly */
        self::assertSame(['id' => '42', 'slug' => 'test'], $params);
    }

    public function testResolveWhenObjectExposesGetArgumentsThenUsesThatMethod(): void
    {
        /** @Given a Slim-style route object */
        $routeObject = new class {
            /** @return array<string, string> */
            public function getArguments(): array
            {
                return ['id' => '1', 'name' => 'dragon'];
            }
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('__route__', $routeObject);

        /** @When resolving parameters */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolve(attributeName: '__route__');

        /** @Then getArguments() result is returned */
        self::assertSame(['id' => '1', 'name' => 'dragon'], $params);
    }

    public function testResolveWhenObjectExposesGetMatchedParamsThenUsesThatMethod(): void
    {
        /** @Given a Mezzio-style route result object */
        $routeResult = new class {
            /** @return array<string, string> */
            public function getMatchedParams(): array
            {
                return ['id' => '99', 'action' => 'view'];
            }
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('routeResult', $routeResult);

        /** @When resolving parameters */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolve(attributeName: 'routeResult');

        /** @Then getMatchedParams() result is returned */
        self::assertSame(['id' => '99', 'action' => 'view'], $params);
    }

    public function testResolveWhenObjectExposesPublicPropertyThenReadsIt(): void
    {
        /** @Given a route object with a public arguments property */
        $routeObject = new class {
            /** @var array<string, string> */
            public array $arguments = ['key' => 'value'];
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('__route__', $routeObject);

        /** @When resolving parameters */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolve(attributeName: '__route__');

        /** @Then the public property value is returned */
        self::assertSame(['key' => 'value'], $params);
    }

    public function testResolveWhenAttributeAbsentThenReturnsEmptyArray(): void
    {
        /** @Given a request with no matching attribute */
        $serverRequest = new ServerRequest(method: 'GET', uri: '/');

        /** @When resolving parameters */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolve(attributeName: '__route__');

        /** @Then an empty array is returned */
        self::assertSame([], $params);
    }

    public function testResolveWhenObjectExposesNoKnownAccessorThenReturnsEmptyArray(): void
    {
        /** @Given a route object without known methods or properties */
        $routeObject = new class {
            public function unknownMethod(): string
            {
                return 'not useful';
            }
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('__route__', $routeObject);

        /** @When resolving parameters */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolve(attributeName: '__route__');

        /** @Then an empty array is returned */
        self::assertSame([], $params);
    }

    public function testResolveFromKnownAttributesWhenSymfonyKeyGivenThenScanFindsIt(): void
    {
        /** @Given params stored under _route_params (Symfony-style) */
        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('_route_params', ['controller' => 'DragonController', 'id' => '5']);

        /** @When scanning known attributes */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolveFromKnownAttributes();

        /** @Then the Symfony-style params are found */
        self::assertSame(['controller' => 'DragonController', 'id' => '5'], $params);
    }

    public function testResolveDirectAttributeWhenKeyPresentThenReturnsValue(): void
    {
        /** @Given a request with direct attributes (Laravel-style) */
        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('id', '123');

        /** @When resolving a direct attribute */
        $resolver = RouteParameterResolver::from(request: $serverRequest);

        /** @Then the direct value is returned */
        self::assertSame('123', $resolver->resolveDirectAttribute(key: 'id'));
        self::assertNull($resolver->resolveDirectAttribute(key: 'nonexistent'));
    }

    public function testResolveWhenObjectHasBothMethodAndPropertyThenMethodWins(): void
    {
        /** @Given an object that has both a method and a property */
        $routeObject = new class {
            /** @var array<string, string> */
            public array $arguments = ['source' => 'property'];

            /** @return array<string, string> */
            public function getArguments(): array
            {
                return ['source' => 'method'];
            }
        };

        $serverRequest = (new ServerRequest(method: 'GET', uri: '/'))
            ->withAttribute('__route__', $routeObject);

        /** @When resolving parameters */
        $params = RouteParameterResolver::from(request: $serverRequest)->resolve(attributeName: '__route__');

        /** @Then the method result takes priority */
        self::assertSame(['source' => 'method'], $params);
    }
}
