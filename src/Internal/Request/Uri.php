<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides access to URI components and route parameters extracted from a PSR-7 ServerRequestInterface.
 *
 * The route parameters are resolved in the following priority:
 * 1. The explicitly specified attribute name (default: `__route__`).
 * 2. A scan of all known framework attribute keys.
 * 3. Direct attribute lookup on the request (for frameworks like Laravel).
 */
final readonly class Uri
{
    private const string ROUTE = '__route__';

    private function __construct(
        private ServerRequestInterface $request,
        private string $routeAttributeName,
        private RouteParameterResolver $resolver
    ) {
    }

    public static function from(ServerRequestInterface $request): Uri
    {
        return new Uri(
            request: $request,
            routeAttributeName: self::ROUTE,
            resolver: RouteParameterResolver::from(request: $request)
        );
    }

    /**
     * Returns the full URI of the request as a string.
     *
     * Delegates to the PSR-7 UriInterface's string representation,
     * which includes scheme, host, path, query string, and fragment.
     *
     * @return string The complete URI string (e.g., "https://api.example.com/v1/dragons?sort=name").
     */
    public function toString(): string
    {
        return $this->request->getUri()->__toString();
    }

    /**
     * Returns a typed wrapper around the query string parameters.
     *
     * @return QueryParameters Provides typed access to individual query parameters via get().
     */
    public function queryParameters(): QueryParameters
    {
        return QueryParameters::from(request: $this->request);
    }

    /**
     * Returns a new Uri instance configured to read route parameters from the given attribute name.
     *
     * @param string $name The request attribute name where route params are stored.
     * @return Uri A new instance targeting the specified attribute.
     */
    public function route(string $name = self::ROUTE): Uri
    {
        return new Uri(
            request: $this->request,
            routeAttributeName: $name,
            resolver: $this->resolver
        );
    }

    /**
     * Retrieves a single route parameter by key.
     *
     * Resolution order:
     * 1. Look up the configured attribute name and extract the key from it.
     * 2. If not found, scan all known framework attribute keys.
     * 3. If still not found, try a direct `getAttribute($key)` on the request.
     * 4. Falls back to `Attribute::from(null)` which provides safe defaults.
     *
     * @param string $key The route parameter name.
     * @return Attribute A typed wrapper around the resolved value.
     */
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
