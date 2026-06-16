<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Transports;

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\NoMoreResponses;
use TinyBlocks\Http\Internal\Client\Cursor;
use TinyBlocks\Http\Internal\Client\RequestRecorder;

/**
 * In-memory {@see Transport} that serves pre-built responses from a FIFO queue.
 *
 * Intended for use in tests and local development to avoid real network calls. Records every
 * request it receives so a consumer can assert on the outbound request it built. Raises
 * {@see NoMoreResponses} when the queue is exhausted.
 */
final readonly class InMemoryTransport implements Transport
{
    private function __construct(private Cursor $cursor, private RequestRecorder $recorder, private array $responses)
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
        return new InMemoryTransport(cursor: new Cursor(), recorder: new RequestRecorder(), responses: $responses);
    }

    public function send(Request $request): Response
    {
        $this->recorder->record(request: $request);

        $index = $this->cursor->advance();

        if (!isset($this->responses[$index])) {
            throw NoMoreResponses::atIndex(index: $index);
        }

        return $this->responses[$index];
    }

    /**
     * Returns the requests received by the transport, in the order they were sent.
     *
     * @return array<int, Request> The recorded outbound requests, oldest first.
     */
    public function receivedRequests(): array
    {
        return $this->recorder->all();
    }

    /**
     * Returns the most recently received request, or null when none was received.
     *
     * @return Request|null The last recorded outbound request, or null before any request was sent.
     */
    public function lastReceivedRequest(): ?Request
    {
        $requests = $this->recorder->all();

        return end($requests) ?: null;
    }
}
