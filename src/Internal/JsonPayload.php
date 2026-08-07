<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal;

use JsonException;

final readonly class JsonPayload
{
    private const int MAX_DEPTH = 64;

    private function __construct(private array $data)
    {
    }

    public static function from(string $content): JsonPayload
    {
        try {
            $decoded = json_decode($content, true, JsonPayload::MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonPayload(data: []);
        }

        return new JsonPayload(data: is_array($decoded) ? $decoded : []);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
