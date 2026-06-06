<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Server\Decoded;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Attribute;
use TinyBlocks\Http\Internal\Server\Request\RouteParameterResolver;

/**
 * Typed accessor for the URI of an incoming HTTP request, including route attributes and query parameters.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/URI
 */
final readonly class Uri
{
    private const string ROUTE = '__route__';

    private function __construct(
        private ServerRequestInterface $request,
        private RouteParameterResolver $resolver,
        private string $routeAttributeName
    ) {
    }

    /**
     * Creates a Uri from a PSR-7 server request.
     *
     * @param ServerRequestInterface $request The incoming PSR-7 server request.
     * @return Uri A Uri scoped to the default route attribute.
     */
    public static function from(ServerRequestInterface $request): Uri
    {
        return new Uri(
            request: $request,
            resolver: RouteParameterResolver::from(request: $request),
            routeAttributeName: Uri::ROUTE
        );
    }

    /**
     * Returns the Attribute associated with the given route key.
     *
     * @param string $key The route attribute key to look up.
     * @return Attribute The Attribute wrapping the resolved value, or wrapping <code>null</code> when absent.
     */
    public function get(string $key): Attribute
    {
        $attributeValue = $this->resolver->resolveAttribute(
            key: $key,
            attributeName: $this->routeAttributeName,
            scanKnownAttributes: $this->routeAttributeName === Uri::ROUTE
        );

        return Attribute::from(value: $attributeValue);
    }

    /**
     * Returns the named route attributes wrapped as typed Attributes, keyed by name.
     *
     * <p>Each key is resolved through the same route resolution as {@see Uri::get()}, so an absent key
     * yields an Attribute wrapping null rather than being omitted. Only the named keys are resolved,
     * keeping the result deterministic and avoiding leaking unrelated PSR-7 request attributes.</p>
     *
     * @param array<int, string> $keys The route attribute keys to resolve.
     * @return array<string, Attribute> A map from each requested key to its resolved Attribute.
     */
    public function only(array $keys): array
    {
        $resolved = [];

        foreach ($keys as $key) {
            $resolved[$key] = $this->get(key: $key);
        }

        return $resolved;
    }

    /**
     * Returns a copy of the Uri scoped to a different route attribute name.
     *
     * When <code>$name</code> is omitted, the Uri is re-scoped to the library's default route
     * attribute key (<code>__route__</code>).
     *
     * @param string $name The route attribute name to scope the Uri to.
     * @return Uri A new Uri scoped to the supplied attribute name.
     */
    public function route(string $name = Uri::ROUTE): Uri
    {
        return new Uri(
            request: $this->request,
            resolver: $this->resolver,
            routeAttributeName: $name
        );
    }

    /**
     * Returns the Uri as a string.
     *
     * @return string The fully composed URI of the underlying request.
     */
    public function toString(): string
    {
        return $this->request->getUri()->__toString();
    }

    /**
     * Returns the query parameters carried by the request URI.
     *
     * @return QueryParameters The QueryParameters value object built from the request.
     */
    public function queryParameters(): QueryParameters
    {
        return QueryParameters::from(request: $this->request);
    }
}
