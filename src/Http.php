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

    private function __construct(string $baseUrl, private Transport $transport)
    {
        $this->resolver = RequestResolver::withBaseUrl(baseUrl: $baseUrl);
    }

    /**
     * Returns a fluent builder used to assemble an Http instance.
     *
     * Both a transport and a base URL must be supplied through the builder
     * before calling build(); otherwise HttpConfigurationInvalid is raised.
     *
     * @return HttpBuilder A new, empty builder.
     */
    public static function create(): HttpBuilder
    {
        return new HttpBuilder(baseUrl: null, transport: null);
    }

    /**
     * Creates an Http instance directly from a base URL and a transport.
     *
     * Explicit single-call alternative to the fluent builder returned by
     * create(). Both arguments are required.
     *
     * @param string $baseUrl The absolute base URL prepended to every request path.
     * @param Transport $transport The transport that delivers resolved requests.
     * @return Http A configured Http façade.
     */
    public static function with(string $baseUrl, Transport $transport): Http
    {
        return new Http(baseUrl: $baseUrl, transport: $transport);
    }

    /**
     * Sends a request through the configured transport and returns the response.
     *
     * The request is first resolved against the configured base URL and the
     * library's JSON defaults. A path that escapes the base URL raises
     * MalformedPath before the transport is invoked. Transport-level failures
     * surface as HttpException subclasses.
     *
     * @param Request $request The outbound request to send.
     * @return Response The response returned by the transport.
     * @throws HttpException When request resolution or the transport fails.
     */
    public function send(Request $request): Response
    {
        return $this->transport->send(request: $this->resolver->resolve(request: $request));
    }
}
