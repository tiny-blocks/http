<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves route parameters from a PSR-7 ServerRequestInterface in a framework-agnostic way.
 *
 * Supports multiple attribute formats used by popular frameworks:
 * - Plain arrays (e.g., Symfony's `_route_params`)
 * - Objects with accessor methods (e.g., Slim's `getArguments()`, Mezzio's `getMatchedParams()`)
 * - Objects with public properties (e.g., `arguments`, `params`, `vars`)
 * - Direct attributes on the request (e.g., Laravel's `getAttribute('id')`)
 */
final readonly class RouteParameterResolver
{
    private const array KNOWN_ATTRIBUTE_KEYS = [
        '__route__',
        '_route_params',
        'route',
        'routing',
        'routeResult',
        'routeInfo'
    ];

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

    private function __construct(private ServerRequestInterface $request)
    {
    }

    public static function from(ServerRequestInterface $request): RouteParameterResolver
    {
        return new RouteParameterResolver(request: $request);
    }

    public function resolve(string $attributeName): array
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

    public function resolveFromKnownAttributes(): array
    {
        foreach (self::KNOWN_ATTRIBUTE_KEYS as $key) {
            $parameters = $this->resolve(attributeName: $key);

            if (!empty($parameters)) {
                return $parameters;
            }
        }

        return [];
    }

    public function resolveDirectAttribute(string $key): mixed
    {
        return $this->request->getAttribute($key);
    }

    private function extractFromObject(object $object): array
    {
        foreach (self::OBJECT_METHODS as $method) {
            if (method_exists($object, $method)) {
                $result = $object->{$method}();

                if (is_array($result)) {
                    return $result;
                }
            }
        }

        foreach (self::OBJECT_PROPERTIES as $property) {
            if (property_exists($object, $property)) {
                $value = $object->{$property};

                if (is_array($value)) {
                    return $value;
                }
            }
        }

        return [];
    }
}
