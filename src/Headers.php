<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\MessageInterface;
use TinyBlocks\Http\Exceptions\HeaderNameIsInvalid;
use TinyBlocks\Http\Exceptions\HeaderValueIsInvalid;

/**
 * Case-insensitive collection of HTTP headers represented as a name-to-value map.
 *
 * Multi-value header lines are folded into a single comma-separated string on construction.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers
 */
final readonly class Headers
{
    private array $entries;
    private array $lowerIndex;

    private function __construct(array $entries)
    {
        $lowerIndex = [];

        foreach ($entries as $name => $value) {
            $lowerIndex[strtolower($name)] = $name;
        }

        $this->entries = $entries;
        $this->lowerIndex = $lowerIndex;
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

        return Headers::fromArray(entries: $entries);
    }

    /**
     * Creates an empty Headers carrying no entries.
     *
     * @return Headers A Headers with no entries.
     */
    public static function empty(): Headers
    {
        return new Headers(entries: []);
    }

    /**
     * Creates a Headers from a name-to-value map.
     *
     * @param array<string, string> $entries The header name to single folded value map; multi-value entries
     *                                        must be pre-folded by the caller.
     * @return Headers A Headers wrapping the supplied entries.
     * @throws HeaderNameIsInvalid If any entry key violates RFC 7230 token rules.
     * @throws HeaderValueIsInvalid If any entry value contains a forbidden control character.
     */
    public static function fromArray(array $entries): Headers
    {
        foreach ($entries as $name => $value) {
            if (!preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/', $name)) {
                throw HeaderNameIsInvalid::for(name: $name);
            }

            if (preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $value) === 1) {
                throw HeaderValueIsInvalid::for(value: $value);
            }
        }

        return new Headers(entries: $entries);
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

        return Headers::fromArray(entries: $entries);
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
     * Returns a copy of these Headers with the named entry replaced or appended.
     *
     * The lookup is case-insensitive: <code>with('content-type', 'text/plain')</code> replaces
     * an existing <code>Content-Type</code> entry regardless of how it was originally cased.
     * When no entry matches, the new header is appended under the supplied name.
     *
     * @param string $name The header name.
     * @param string $value The replacement or new header value.
     * @return Headers A new instance carrying the updated header.
     * @throws HeaderNameIsInvalid If the name violates RFC 7230 token rules.
     * @throws HeaderValueIsInvalid If the value contains a forbidden control character.
     */
    public function with(string $name, string $value): Headers
    {
        $key = strtolower($name);
        $entries = $this->entries;

        if (isset($this->lowerIndex[$key])) {
            $entries[$this->lowerIndex[$key]] = $value;
        } else {
            $entries[$name] = $value;
        }

        return Headers::fromArray(entries: $entries);
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
     * Returns the headers as a name to value map.
     *
     * @return array<string, string> The header name to single folded value map.
     */
    public function toArray(): array
    {
        return $this->entries;
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

        return Headers::fromArray(entries: $merged);
    }
}
