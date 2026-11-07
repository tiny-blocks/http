<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Response;

use TinyBlocks\Http\Exceptions\HeaderIsConnectionSpecific;
use TinyBlocks\Http\Internal\HeaderName;
use TinyBlocks\Http\Internal\HeaderValue;

final readonly class ResponseField
{
    private const string TE = 'te';
    private const string TRAILERS = 'trailers';
    private const array CONNECTION_SPECIFIC = [
        'upgrade',
        'connection',
        'keep-alive',
        'proxy-connection',
        'transfer-encoding'
    ];

    private function __construct(private string $name, private array $values)
    {
    }

    public static function from(string $name, string|array $value): ResponseField
    {
        $fieldName = HeaderName::from(value: $name)->toString();
        $loweredName = strtolower($fieldName);
        $values = array_map(
            static fn(string $fieldValue): string => HeaderValue::from(value: $fieldValue)->toString(),
            is_array($value) ? $value : [$value]
        );

        $isOtherThanTrailers = static fn(string $fieldValue): bool => strtolower(trim($fieldValue)) !== self::TRAILERS;

        if (
            in_array($loweredName, self::CONNECTION_SPECIFIC, true)
            || ($loweredName === self::TE && array_any($values, $isOtherThanTrailers))
        ) {
            throw HeaderIsConnectionSpecific::for(name: $fieldName);
        }

        return new ResponseField(name: $fieldName, values: $values);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function values(): array
    {
        return $this->values;
    }
}
