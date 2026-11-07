<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Response;

use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headerable;

final readonly class ResponseHeaders
{
    private const string CONTENT_TYPE = 'Content-Type';

    private function __construct(private array $headers)
    {
    }

    private static function mergeInto(Headerable $header, array $merged): array
    {
        foreach ($header->toArray() as $name => $value) {
            $field = ResponseField::from(name: $name, value: $value);
            $key = $field->name();
            $merged[$key] = isset($merged[$key]) ? array_merge($merged[$key], $field->values()) : $field->values();
        }

        return $merged;
    }

    public static function fromWithDefaultContentType(Headerable ...$headers): ResponseHeaders
    {
        $provided = ResponseHeaders::fromWithoutDefaultContentType(...$headers);

        if ($provided->hasHeader(name: ResponseHeaders::CONTENT_TYPE)) {
            return $provided;
        }

        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        return new ResponseHeaders(
            headers: ResponseHeaders::mergeInto(header: $contentType, merged: $provided->toArray())
        );
    }

    /**
     * Merges the supplied headers exactly as given, with no media type of the library's own. A response built
     * without a body has no payload to describe, and announcing one over zero bytes misleads a strict client
     * into parsing a body that was never sent.
     */
    public static function fromWithoutDefaultContentType(Headerable ...$headers): ResponseHeaders
    {
        $merged = [];

        foreach ($headers as $header) {
            $merged = ResponseHeaders::mergeInto(header: $header, merged: $merged);
        }

        return new ResponseHeaders(headers: $merged);
    }

    private function findKey(string $name): ?string
    {
        $lowered = strtolower($name);

        return array_find(array_keys($this->headers), static fn(string $key): bool => strtolower($key) === $lowered);
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

    public function hasHeader(string $name): bool
    {
        return !empty($this->getByName(name: $name));
    }

    public function withAdded(string $name, string|array $value): ResponseHeaders
    {
        $headers = $this->headers;
        $field = ResponseField::from(name: $name, value: $value);
        $appended = $field->values();
        $existingKey = $this->findKey(name: $field->name());

        if (is_null($existingKey)) {
            $headers[$field->name()] = $appended;

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

    public function removeByName(string $name): ResponseHeaders
    {
        $headers = $this->headers;
        $existingKey = $this->findKey(name: $name);

        if (!is_null($existingKey)) {
            unset($headers[$existingKey]);
        }

        return new ResponseHeaders(headers: $headers);
    }

    public function withReplaced(string $name, string|array $value): ResponseHeaders
    {
        $headers = $this->headers;
        $field = ResponseField::from(name: $name, value: $value);
        $targetKey = ($this->findKey(name: $field->name()) ?? $field->name());
        $headers[$targetKey] = $field->values();

        return new ResponseHeaders(headers: $headers);
    }
}
