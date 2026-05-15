<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\MessageInterface;

final readonly class Headers
{
    /** @var array<string, string> */
    private array $entries;
    /** @var array<string, string> */
    private array $lowerIndex;

    /** @param array<string, string> $entries */
    public function __construct(array $entries)
    {
        $lowerIndex = [];

        foreach ($entries as $name => $value) {
            $lowerIndex[strtolower($name)] = $name;
        }

        $this->entries = $entries;
        $this->lowerIndex = $lowerIndex;
    }

    public static function fromMessage(MessageInterface $message): Headers
    {
        $entries = array_map(function ($values) {
            return implode(', ', $values);
        }, $message->getHeaders());

        return new Headers(entries: $entries);
    }

    public static function from(Headerable ...$headers): Headers
    {
        $entries = [];

        foreach ($headers as $header) {
            foreach ($header->toArray() as $name => $value) {
                $entries[$name] = is_array($value) ? implode(', ', $value) : $value;
            }
        }

        return new Headers(entries: $entries);
    }

    public function has(string $name): bool
    {
        return isset($this->lowerIndex[strtolower($name)]);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->entries;
    }

    public function get(string $name): ?string
    {
        $key = strtolower($name);

        if (!isset($this->lowerIndex[$key])) {
            return null;
        }

        return $this->entries[$this->lowerIndex[$key]];
    }

    public function applyTo(MessageInterface $message): MessageInterface
    {
        $applied = $message;

        foreach ($this->entries as $name => $value) {
            $applied = $applied->withHeader($name, $value);
        }

        return $applied;
    }

    public function mergedWith(Headers $other): Headers
    {
        $merged = $this->entries;

        foreach ($other->entries as $name => $value) {
            if (isset($this->lowerIndex[strtolower($name)])) {
                continue;
            }

            $merged[$name] = $value;
        }

        return new Headers(entries: $merged);
    }
}
