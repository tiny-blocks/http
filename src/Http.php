<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Internal\Client\RequestResolver;

final readonly class Http
{
    private RequestResolver $resolver;

    public function __construct(string $baseUrl, private Transport $transport)
    {
        $this->resolver = RequestResolver::withBaseUrl(baseUrl: $baseUrl);
    }

    public static function create(): HttpBuilder
    {
        return new HttpBuilder(baseUrl: null, transport: null);
    }

    /**
     * Sends a request through the configured transport and returns the response.
     *
     * @param Request $request The outbound request to send.
     * @return Response The response returned by the transport.
     * @throws HttpException When resolution or the transport fails.
     */
    public function send(Request $request): Response
    {
        return $this->transport->send(request: $this->resolver->resolve(request: $request));
    }
}
