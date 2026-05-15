<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;

final class NoMoreResponses extends LogicException
{
    private const string REASON_TEMPLATE = 'InMemoryTransport has no response queued at index %d.';

    public static function atIndex(int $index): NoMoreResponses
    {
        return new self(sprintf(self::REASON_TEMPLATE, $index));
    }
}
