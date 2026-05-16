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

    /**
     * Creates a Headers from a PSR-7 message, folding multi-value headers with commas.
     *
     * @param MessageInterface $message The PSR-7 message providing the headers.
     * @return Headers A Headers carrying each header from the message, with multi-value entries folded.
     */
    public static function fromMessage(MessageInterface $message): Headers
    {
        $entries = array_map(
            static fn(array $values): string => implode(', ', $values),
            $message->getHeaders()
        );

        return new Headers(entries: $entries);
    }

    /**
     * Creates a Headers from a list of Headerable contributors, with the last one winning on collision.
     *
     * @param Headerable ...$headers The Headerable contributors merged into the result.
     * @return Headers A Headers carrying every entry from the supplied contributors.
     */
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

    /**
     * Tells whether a header with the given name exists, case-insensitively.
     *
     * @param string $name The header name to look up.
     * @return bool True when a header with that name is present regardless of casing, otherwise false.
     */
    public function has(string $name): bool
    {
        return isset($this->lowerIndex[strtolower($name)]);
    }

    /**
     * Returns the headers as a name to value map.
     *
     * @return array<string, string> The header name to single folded value map.
     */
    public function toArray(): array
    {
        return $this->entries;
    }

    /**
     * Returns the value associated with the given header name, looking up case-insensitively.
     *
     * @param string $name The header name to look up.
     * @return string|null The folded header value, or <code>null</code> when no entry matches.
     */
    public function get(string $name): ?string
    {
        $key = strtolower($name);

        if (!isset($this->lowerIndex[$key])) {
            return null;
        }

        return $this->entries[$this->lowerIndex[$key]];
    }

    /**
     * Applies every header in this collection to the given PSR-7 message, returning a new instance.
     *
     * @template T of MessageInterface
     * @param T $message The PSR-7 message that receives the headers.
     * @return T A new message instance carrying every header.
     */
    public function applyTo(MessageInterface $message): MessageInterface
    {
        $applied = $message;

        foreach ($this->entries as $name => $value) {
            $applied = $applied->withHeader($name, $value);
        }

        return $applied;
    }

    /**
     * Returns a copy of these Headers merged with another instance, with existing entries winning on collision.
     *
     * @param Headers $other The Headers whose entries are merged under the existing ones.
     * @return Headers A new instance carrying the union of both sets of headers.
     */
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
