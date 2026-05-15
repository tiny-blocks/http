<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Request;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Shared\Attribute;

final readonly class QueryParameters
{
    /** @param array<string, mixed> $data */
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
