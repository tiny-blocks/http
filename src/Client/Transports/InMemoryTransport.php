<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Transports;

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\NoMoreResponses;
use TinyBlocks\Http\Internal\Client\Cursor;

/**
 * In-memory {@see Transport} that serves pre-built responses from a FIFO queue.
 *
 * Intended for use in tests and local development to avoid real network calls.
 * Raises {@see NoMoreResponses} when the queue is exhausted.
 */
final readonly class InMemoryTransport implements Transport
{
    private function __construct(private Cursor $cursor, private array $responses)
    {
    }

    /**
     * Creates an InMemoryTransport seeded with a FIFO queue of responses.
     *
     * @param array<int, Response> $responses The pre-built responses served in order on each send.
     * @return InMemoryTransport A transport that returns each seeded response in sequence.
     */
    public static function with(array $responses): InMemoryTransport
    {
        return new InMemoryTransport(cursor: new Cursor(), responses: $responses);
    }

    public function send(Request $request): Response
    {
        $index = $this->cursor->advance();

        if (!isset($this->responses[$index])) {
            throw NoMoreResponses::atIndex(index: $index);
        }

        return $this->responses[$index];
    }
}
