<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use TinyBlocks\Http\Exceptions\HttpException;

/**
 * Port abstracting outbound HTTP dispatch, decoupling Http from any concrete delivery mechanism.
 *
 * Built-in: {@see NetworkTransport} (PSR-18 backed) and {@see InMemoryTransport} (testing).
 * Implementations may decorate an inner Transport for cross-cutting concerns such as
 * retry, logging, or circuit breaking.
 */
interface Transport
{
    /**
     * Sends an outbound HTTP request and returns the response.
     *
     * The request received here is expected to be fully resolved by the caller (absolute URL with
     * query embedded, default headers already merged).
     * Transport implementations must translate transport-level failures into HttpException.
     *
     * @param Request $request The fully resolved outbound request.
     * @return Response The response produced by the transport.
     * @throws HttpException When the transport fails.
     */
    public function send(Request $request): Response;
}
