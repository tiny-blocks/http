<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response;

use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headers;

final readonly class ResponseHeaders implements Headers
{
    private function __construct(private array $headers)
    {
    }

    public static function fromOrDefault(Headers ...$headers): ResponseHeaders
    {
        if (empty($headers)) {
            return new ResponseHeaders(headers: ContentType::applicationJson(charset: Charset::UTF_8)->toArray());
        }

        $merged = [];

        foreach ($headers as $header) {
            foreach ($header->toArray() as $name => $values) {
                $merged[$name] = isset($merged[$name]) ? array_merge($merged[$name], $values) : $values;
            }
        }

        return new ResponseHeaders(headers: $merged);
    }

    public function getByName(string $name): array
    {
        $key = $this->findKey(name: $name);

        return $key === null ? [] : $this->headers[$key];
    }

    public function hasHeader(string $name): bool
    {
        return !empty($this->getByName(name: $name));
    }

    public function removeByName(string $name): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);

        if ($existingKey !== null) {
            unset($headers[$existingKey]);
        }

        return new ResponseHeaders(headers: $headers);
    }

    public function withReplaced(string $name, mixed $value): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);
        $targetKey = $existingKey ?? $name;
        $headers[$targetKey] = [$value];

        return new ResponseHeaders(headers: $headers);
    }

    public function withAdded(string $name, mixed $value): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);

        if ($existingKey === null) {
            $headers[$name] = [$value];

            return new ResponseHeaders(headers: $headers);
        }

        $existingValues = $headers[$existingKey];

        if (in_array($value, $existingValues, strict: true)) {
            return new ResponseHeaders(headers: $headers);
        }

        $existingValues[] = $value;
        $headers[$existingKey] = $existingValues;

        return new ResponseHeaders(headers: $headers);
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    private function findKey(string $name): ?string
    {
        $lowered = strtolower($name);

        foreach (array_keys($this->headers) as $key) {
            if (strtolower($key) === $lowered) {
                return $key;
            }
        }

        return null;
    }
}
