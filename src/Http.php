<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Internal\Client\RequestResolver;

final readonly class Http
{
    private RequestResolver $resolver;

    private function __construct(string $baseUrl, private Transport $transport)
    {
        $this->resolver = RequestResolver::withBaseUrl(baseUrl: $baseUrl);
    }

    public static function create(): HttpBuilder
    {
        return new HttpBuilder(baseUrl: null, transport: null);
    }

    public static function with(string $baseUrl, Transport $transport): Http
    {
        return new Http(baseUrl: $baseUrl, transport: $transport);
    }

    public function send(Request $request): Response
    {
        return $this->transport->send(request: $this->resolver->resolve(request: $request));
    }
}
