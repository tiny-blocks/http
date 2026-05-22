<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\BaseUrlIsInvalid;
use TinyBlocks\Http\Exceptions\HttpConfigurationInvalid;
use TinyBlocks\Http\Internal\Client\BaseUrl;

/**
 * Fluent builder for <code>Http</code> instances.
 *
 * Accepts a base URL and Transport in any order; the configuration is validated only when
 * <code>HttpBuilder::build()</code> is called.
 */
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
     * @throws BaseUrlIsInvalid If the URL is not empty, http://, or https://. Validation happens immediately,
     *                          before build() is called.
     */
    public function withBaseUrl(string $url): HttpBuilder
    {
        $baseUrl = BaseUrl::from($url);
        return new HttpBuilder(baseUrl: $baseUrl->toString(), transport: $this->transport);
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
     * Assembles the configured Http facade.
     *
     * Both a base URL and a transport must have been supplied via withBaseUrl()
     * and withTransport() before this call.
     *
     * @return Http A configured Http facade.
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
