<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\MessageInterface;

final readonly class Headers
{
    private array $entries;
    private array $lowerIndex;

    public function __construct(array $entries)
    {
        $lowerIndex = [];

        foreach ($entries as $name => $value) {
            $lowerIndex[strtolower($name)] = $name;
        }

        $this->entries = $entries;
        $this->lowerIndex = $lowerIndex;
    }

    public static function fromArray(array $entries): Headers
    {
        return new Headers(entries: $entries);
    }

    public static function fromMessage(MessageInterface $message): Headers
    {
        $entries = [];

        foreach ($message->getHeaders() as $name => $values) {
            $entries[$name] = implode(', ', $values);
        }

        return new Headers(entries: $entries);
    }

    public static function from(Headerable ...$headerables): Headers
    {
        $entries = [];

        foreach ($headerables as $headerable) {
            foreach ($headerable->toArray() as $name => $value) {
                $entries[$name] = is_array($value) ? implode(', ', $value) : $value;
            }
        }

        return new Headers(entries: $entries);
    }

    public function has(string $name): bool
    {
        return isset($this->lowerIndex[strtolower($name)]);
    }

    public function toArray(): array
    {
        return $this->entries;
    }

    public function applyTo(MessageInterface $message): MessageInterface
    {
        $applied = $message;

        foreach ($this->entries as $name => $value) {
            $applied = $applied->withHeader($name, $value);
        }

        return $applied;
    }

    public function get(string $name): ?string
    {
        $key = strtolower($name);

        if (!isset($this->lowerIndex[$key])) {
            return null;
        }

        return $this->entries[$this->lowerIndex[$key]];
    }

    public function mergedWith(array $defaults): Headers
    {
        $merged = [];

        foreach ($defaults as $name => $value) {
            if (isset($this->lowerIndex[strtolower($name)])) {
                continue;
            }

            $merged[$name] = $value;
        }

        foreach ($this->entries as $name => $value) {
            $merged[$name] = $value;
        }

        return new Headers(entries: $merged);
    }
}
