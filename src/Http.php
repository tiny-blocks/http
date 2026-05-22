<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\BaseUrlIsInvalid;
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Internal\Client\BaseUrl;
use TinyBlocks\Http\Internal\Client\RequestResolver;

/**
 * Facade for sending outbound HTTP requests through a configured Transport.
 *
 * Resolves each Request against the configured base URL and JSON defaults before delegating to
 * the Transport. Constructed via the fluent builder returned by <code>Http::create()</code>
 * or the explicit factory <code>Http::with()</code>.
 *
 * Path normalization of the remote URL (for example collapsing <code>...</code> segments) is the
 * responsibility of the remote server.
 */
final readonly class Http
{
    private RequestResolver $resolver;

    private function __construct(string $baseUrl, private Transport $transport)
    {
        $baseUrl = BaseUrl::from(value: $baseUrl);
        $this->resolver = RequestResolver::withBaseUrl(baseUrl: $baseUrl->toString());
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
     * Creates an Http instance directly from a base URL and transport.
     *
     * Explicit single-call alternative to the fluent builder returned by
     * create(). Both arguments are required.
     *
     * @param string $baseUrl The absolute base URL prepended to every request path.
     * @param Transport $transport The transport that delivers resolved requests.
     * @return Http A configured Http facade.
     * @throws BaseUrlIsInvalid If the base URL is not an accepted form.
     */
    public static function with(string $baseUrl, Transport $transport): Http
    {
        return new Http(baseUrl: $baseUrl, transport: $transport);
    }

    /**
     * Sends a request through the configured transport and returns the response.
     *
     * The request is first resolved against the configured base URL and the JSON defaults
     * (Accept: application/json, Content-Type: application/json), which custom headers in the
     * request override. A path that escapes the base URL raises
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
