<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Response;

use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headerable;

final readonly class ResponseHeaders
{
    private function __construct(private array $headers)
    {
    }

    public static function fromOrDefault(Headerable ...$headers): ResponseHeaders
    {
        if (empty($headers)) {
            return new ResponseHeaders(headers: ContentType::applicationJson(charset: Charset::UTF_8)->toArray());
        }

        $merged = [];

        foreach ($headers as $header) {
            foreach ($header->toArray() as $name => $value) {
                $values = is_array($value) ? $value : [$value];
                $merged[$name] = isset($merged[$name]) ? array_merge($merged[$name], $values) : $values;
            }
        }

        return new ResponseHeaders(headers: $merged);
    }

    public function hasHeader(string $name): bool
    {
        return !empty($this->getByName(name: $name));
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public function getByName(string $name): array
    {
        $key = $this->findKey(name: $name);

        return is_null($key) ? [] : $this->headers[$key];
    }

    private function findKey(string $name): ?string
    {
        $lowered = strtolower($name);

        return array_find(array_keys($this->headers), static fn(string $key): bool => strtolower($key) === $lowered);
    }

    public function withReplaced(string $name, string|array $value): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);
        $targetKey = $existingKey ?? $name;
        $headers[$targetKey] = is_array($value) ? $value : [$value];

        return new ResponseHeaders(headers: $headers);
    }

    public function removeByName(string $name): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);

        if (!is_null($existingKey)) {
            unset($headers[$existingKey]);
        }

        return new ResponseHeaders(headers: $headers);
    }

    public function withAdded(string $name, string|array $value): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);
        $appended = is_array($value) ? $value : [$value];

        if (is_null($existingKey)) {
            $headers[$name] = $appended;

            return new ResponseHeaders(headers: $headers);
        }

        $existingValues = $headers[$existingKey];

        foreach ($appended as $next) {
            if (!in_array($next, $existingValues, true)) {
                $existingValues[] = $next;
            }
        }

        $headers[$existingKey] = $existingValues;

        return new ResponseHeaders(headers: $headers);
    }
}
