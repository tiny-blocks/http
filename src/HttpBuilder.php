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

    public function withBaseUrl(string $url): HttpBuilder
    {
        return new HttpBuilder(baseUrl: $url, transport: $this->transport);
    }

    public function withTransport(Transport $transport): HttpBuilder
    {
        return new HttpBuilder(baseUrl: $this->baseUrl, transport: $transport);
    }

    public function build(): Http
    {
        if (is_null($this->transport)) {
            throw HttpConfigurationInvalid::missingTransport();
        }

        if (is_null($this->baseUrl)) {
            throw HttpConfigurationInvalid::missingBaseUrl();
        }

        return new Http(baseUrl: $this->baseUrl, transport: $this->transport);
    }
}
