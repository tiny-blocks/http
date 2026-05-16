<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Request;

use Psr\Http\Message\ServerRequestInterface;

final readonly class RouteParameterResolver
{
    private const array OBJECT_METHODS = [
        'getArguments',
        'getMatchedParams',
        'getParameters',
        'getParams'
    ];

    private const array OBJECT_PROPERTIES = [
        'arguments',
        'params',
        'vars',
        'parameters'
    ];

    private const array KNOWN_ATTRIBUTE_KEYS = [
        '__route__',
        '_route_params',
        'route',
        'routing',
        'routeResult',
        'routeInfo'
    ];

    private function __construct(private ServerRequestInterface $request)
    {
    }

    public static function from(ServerRequestInterface $request): RouteParameterResolver
    {
        return new RouteParameterResolver(request: $request);
    }

    public function resolveAttribute(string $key, string $attributeName, bool $scanKnownAttributes): mixed
    {
        $parameters = $this->resolve(attributeName: $attributeName);

        if (array_key_exists($key, $parameters)) {
            return $parameters[$key];
        }

        $attribute = $this->request->getAttribute($attributeName);

        if (is_scalar($attribute)) {
            return $attribute;
        }

        return $this->resolveFallback(key: $key, scanKnownAttributes: $scanKnownAttributes);
    }

    private function resolveFallback(string $key, bool $scanKnownAttributes): mixed
    {
        if ($scanKnownAttributes) {
            $allKnown = $this->resolveFromKnownAttributes();

            if (array_key_exists($key, $allKnown)) {
                return $allKnown[$key];
            }
        }

        return $this->request->getAttribute($key);
    }

    private function resolveFromKnownAttributes(): array
    {
        foreach (RouteParameterResolver::KNOWN_ATTRIBUTE_KEYS as $key) {
            $parameters = $this->resolve(attributeName: $key);

            if (!empty($parameters)) {
                return $parameters;
            }
        }

        return [];
    }

    private function resolve(string $attributeName): array
    {
        $attribute = $this->request->getAttribute($attributeName);

        if (is_array($attribute)) {
            return $attribute;
        }

        if (is_object($attribute)) {
            return $this->extractFromObject(object: $attribute);
        }

        return [];
    }

    private function extractFromObject(object $object): array
    {
        foreach (RouteParameterResolver::OBJECT_METHODS as $method) {
            if (method_exists($object, $method)) {
                $parameters = $object->{$method}();

                if (is_array($parameters)) {
                    return $parameters;
                }
            }
        }

        foreach (RouteParameterResolver::OBJECT_PROPERTIES as $property) {
            if (property_exists($object, $property)) {
                $parameters = $object->{$property};

                if (is_array($parameters)) {
                    return $parameters;
                }
            }
        }

        return [];
    }
}
