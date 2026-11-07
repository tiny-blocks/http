<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Response;

use TinyBlocks\Http\Exceptions\ProtocolVersionIsUnsupported;

enum ProtocolVersion: string
{
    case HTTP_1_0 = '1.0';
    case HTTP_1_1 = '1.1';
    case HTTP_2 = '2';
    case HTTP_3 = '3';

    public static function fromVersion(string $version): ProtocolVersion
    {
        $canonical = match ($version) {
            '2.0'   => self::HTTP_2->value,
            '3.0'   => self::HTTP_3->value,
            default => $version
        };

        return (ProtocolVersion::tryFrom($canonical) ?? throw ProtocolVersionIsUnsupported::for(version: $version));
    }
}
