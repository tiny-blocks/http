<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Internal\Request;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Request\RouteParameterResolver;

final class RouteParameterResolverTest extends TestCase
{
    public function testResolveWithArrayAttribute(): void
    {
        /** @Given a request with an array attribute under __route__ */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => ['id' => '42', 'slug' => 'test'],
                default     => null
            });

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then the array should be returned directly */
        self::assertSame(['id' => '42', 'slug' => 'test'], $params);
    }

    public function testResolveWithObjectUsingGetArguments(): void
    {
        /** @Given a Slim-style route object */
        $routeObject = new class {
            public function getArguments(): array
            {
                return ['id' => '1', 'name' => 'dragon'];
            }
        };

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $routeObject,
                default     => null
            });

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then getArguments() result should be returned */
        self::assertSame(['id' => '1', 'name' => 'dragon'], $params);
    }

    public function testResolveWithObjectUsingGetMatchedParams(): void
    {
        /** @Given a Mezzio-style route result object */
        $routeResult = new class {
            public function getMatchedParams(): array
            {
                return ['id' => '99', 'action' => 'view'];
            }
        };

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                'routeResult' => $routeResult,
                default       => null
            });

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: 'routeResult');

        /** @Then getMatchedParams() result should be returned */
        self::assertSame(['id' => '99', 'action' => 'view'], $params);
    }

    public function testResolveWithObjectUsingPublicProperty(): void
    {
        /** @Given a route object with a public arguments property */
        $routeObject = new class {
            public array $arguments = ['key' => 'value'];
        };

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $routeObject,
                default     => null
            });

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then the public property value should be returned */
        self::assertSame(['key' => 'value'], $params);
    }

    public function testResolveReturnsEmptyArrayWhenAttributeIsNull(): void
    {
        /** @Given a request with no matching attribute */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturn(null);

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then an empty array should be returned */
        self::assertSame([], $params);
    }

    public function testResolveReturnsEmptyArrayForUnextractableObject(): void
    {
        /** @Given a route object without known methods or properties */
        $routeObject = new class {
            public function unknownMethod(): string
            {
                return 'not useful';
            }
        };

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $routeObject,
                default     => null
            });

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then an empty array should be returned */
        self::assertSame([], $params);
    }

    public function testResolveFromKnownAttributesScansMultipleKeys(): void
    {
        /** @Given params stored under _route_params (Symfony-style) */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '_route_params' => ['controller' => 'DragonController', 'id' => '5'],
                default         => null
            });

        /** @When scanning known attributes */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolveFromKnownAttributes();

        /** @Then the Symfony-style params should be found */
        self::assertSame(['controller' => 'DragonController', 'id' => '5'], $params);
    }

    public function testResolveDirectAttribute(): void
    {
        /** @Given a request with direct attributes (Laravel-style) */
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                'id'    => '123',
                default => null
            });

        /** @When resolving a direct attribute */
        $resolver = RouteParameterResolver::from(request: $serverRequest);

        /** @Then the direct value should be returned */
        self::assertSame('123', $resolver->resolveDirectAttribute(key: 'id'));
        self::assertNull($resolver->resolveDirectAttribute(key: 'nonexistent'));
    }

    public function testResolveWithObjectMethodPriorityOverProperty(): void
    {
        /** @Given an object that has both a method and a property */
        $routeObject = new class {
            public array $arguments = ['source' => 'property'];

            public function getArguments(): array
            {
                return ['source' => 'method'];
            }
        };

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest
            ->method('getAttribute')
            ->willReturnCallback(static fn(string $name) => match ($name) {
                '__route__' => $routeObject,
                default     => null
            });

        /** @When resolving parameters */
        $resolver = RouteParameterResolver::from(request: $serverRequest);
        $params = $resolver->resolve(attributeName: '__route__');

        /** @Then the method result should take priority */
        self::assertSame(['source' => 'method'], $params);
    }
}
