<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Request;

use Psr\Http\Message\ServerRequestInterface;

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

    /** @return array<int|string, mixed> */
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

    /** @return array<int|string, mixed> */
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

    /** @return array<int|string, mixed> */
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
