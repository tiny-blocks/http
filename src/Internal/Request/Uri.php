<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

use Psr\Http\Message\ServerRequestInterface;

final readonly class Uri
{
    private const string ROUTE = '__route__';

    private function __construct(private ServerRequestInterface $request, private string $routeAttributeName)
    {
    }

    public static function from(ServerRequestInterface $request): Uri
    {
        return new Uri(request: $request, routeAttributeName: self::ROUTE);
    }

    public function route(string $name = self::ROUTE): Uri
    {
        return new Uri(request: $this->request, routeAttributeName: $name);
    }

    public function get(string $key): Attribute
    {
        $attribute = $this->request->getAttribute($this->routeAttributeName);

        if (is_array($attribute)) {
            return Attribute::from(value: $attribute[$key] ?? null);
        }

        return Attribute::from(value: $attribute);
    }
}
