<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;

final readonly class HttpBuilder
{
    public function __construct(private ?string $baseUrl, private ?Transport $transport)
    {
    }

    /**
     * Returns a new builder carrying the given base URL.
     *
     * @param string $url The absolute base URL prepended to every request path.
     * @return HttpBuilder A new builder instance.
     */
    public function withBaseUrl(string $url): HttpBuilder
    {
        return new HttpBuilder(baseUrl: $url, transport: $this->transport);
    }

    /**
     * Returns a new builder carrying the given transport.
     *
     * @param Transport $transport The transport that will deliver resolved requests.
     * @return HttpBuilder A new builder instance.
     */
    public function withTransport(Transport $transport): HttpBuilder
    {
        return new HttpBuilder(baseUrl: $this->baseUrl, transport: $transport);
    }

    /**
     * Assembles the configured Http façade.
     *
     * Both a base URL and a transport must have been supplied via withBaseUrl()
     * and withTransport() before this call.
     *
     * @return Http A configured Http façade.
     * @throws HttpConfigurationInvalid When the base URL or the transport is missing.
     */
    public function build(): Http
    {
        if (is_null($this->transport)) {
            throw HttpConfigurationInvalid::missingTransport();
        }

        if (is_null($this->baseUrl)) {
            throw HttpConfigurationInvalid::missingBaseUrl();
        }

        return Http::with(baseUrl: $this->baseUrl, transport: $this->transport);
    }
}
