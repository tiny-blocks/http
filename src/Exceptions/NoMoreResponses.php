<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;

final class NoMoreResponses extends LogicException implements HttpException
{
    private const string REASON_TEMPLATE = 'InMemoryTransport has no response queued at index %d.';

    private function __construct(int $index)
    {
        $template = NoMoreResponses::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $index));
    }

    /**
     * Creates a NoMoreResponses signaling that the seeded queue is exhausted at the given index.
     *
     * @param int $index The position the in-memory transport tried to read past the end of the queue.
     * @return NoMoreResponses The composed exception describing the exhausted-queue state.
     */
    public static function atIndex(int $index): NoMoreResponses
    {
        return new NoMoreResponses(index: $index);
    }
}
