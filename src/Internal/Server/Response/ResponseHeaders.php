<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Response;

use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headerable;

final readonly class ResponseHeaders
{
    /** @param array<string, list<string>> $headers */
    private function __construct(private array $headers)
    {
    }

    public static function fromOrDefault(Headerable ...$headers): ResponseHeaders
    {
        if (empty($headers)) {
            return new ResponseHeaders(headers: ContentType::applicationJson(charset: Charset::UTF_8)->toArray());
        }

        /** @var array<string, list<string>> $merged */
        $merged = [];

        foreach ($headers as $header) {
            foreach ($header->toArray() as $name => $value) {
                $values = is_array($value) ? $value : [$value];
                $merged[$name] = isset($merged[$name]) ? array_merge($merged[$name], $values) : $values;
            }
        }

        return new ResponseHeaders(headers: $merged);
    }

    /** @return list<string> */
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

    /** @param string|list<string> $value */
    public function withReplaced(string $name, string|array $value): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);
        $targetKey = $existingKey ?? $name;
        $headers[$targetKey] = is_array($value) ? $value : [$value];

        return new ResponseHeaders(headers: $headers);
    }

    /** @param string|list<string> $value */
    public function withAdded(string $name, string|array $value): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);
        $appended = is_array($value) ? $value : [$value];

        if ($existingKey === null) {
            $headers[$name] = $appended;

            return new ResponseHeaders(headers: $headers);
        }

        $existingValues = $headers[$existingKey];

        foreach ($appended as $next) {
            if (!in_array($next, $existingValues, strict: true)) {
                $existingValues[] = $next;
            }
        }

        $headers[$existingKey] = $existingValues;

        return new ResponseHeaders(headers: $headers);
    }

    /** @return array<string, list<string>> */
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
