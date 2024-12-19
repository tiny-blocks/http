<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response;

final readonly class ProtocolVersion
{
    private const string DEFAULT_PROTOCOL_VERSION = '1.1';

    private function __construct(public string $version)
    {
    }

    public static function default(): ProtocolVersion
    {
        return new ProtocolVersion(version: self::DEFAULT_PROTOCOL_VERSION);
    }

    public static function from(string $version): ProtocolVersion
    {
        return empty($version) ? self::default() : new ProtocolVersion(version: $version);
    }
}
