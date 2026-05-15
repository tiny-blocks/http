<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Request;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Shared\Attribute;

final readonly class Uri
{
    private const string ROUTE = '__route__';

    private function __construct(
        private ServerRequestInterface $request,
        private RouteParameterResolver $resolver,
        private string $routeAttributeName
    ) {
    }

    public static function from(ServerRequestInterface $request): Uri
    {
        return new Uri(
            request: $request,
            resolver: RouteParameterResolver::from(request: $request),
            routeAttributeName: self::ROUTE
        );
    }

    public function toString(): string
    {
        return $this->request->getUri()->__toString();
    }

    public function queryParameters(): QueryParameters
    {
        return QueryParameters::from(request: $this->request);
    }

    public function route(string $name = self::ROUTE): Uri
    {
        return new Uri(
            request: $this->request,
            resolver: $this->resolver,
            routeAttributeName: $name
        );
    }

    public function get(string $key): Attribute
    {
        $value = $this->resolveValue(key: $key);

        return Attribute::from(value: $value);
    }

    private function resolveValue(string $key): mixed
    {
        $parameters = $this->resolver->resolve(attributeName: $this->routeAttributeName);

        if (array_key_exists($key, $parameters)) {
            return $parameters[$key];
        }

        $attribute = $this->request->getAttribute($this->routeAttributeName);

        if (is_scalar($attribute)) {
            return $attribute;
        }

        return $this->resolveFromFallbacks(key: $key);
    }

    private function resolveFromFallbacks(string $key): mixed
    {
        if ($this->routeAttributeName === self::ROUTE) {
            $allKnown = $this->resolver->resolveFromKnownAttributes();

            if (array_key_exists($key, $allKnown)) {
                return $allKnown[$key];
            }
        }

        return $this->resolver->resolveDirectAttribute(key: $key);
    }
}
