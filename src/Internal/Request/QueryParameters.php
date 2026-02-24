<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

use Psr\Http\Message\ServerRequestInterface;

final readonly class QueryParameters
{
    private function __construct(private array $data)
    {
    }

    public static function from(ServerRequestInterface $request): QueryParameters
    {
        return new QueryParameters(data: $request->getQueryParams());
    }

    public function get(string $key): Attribute
    {
        $value = ($this->data[$key] ?? null);

        return Attribute::from(value: $value);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
