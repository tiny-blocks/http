<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Drivers\Slim;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Stream;

final readonly class RequestFactory
{
    public static function postFrom(array $payload): ServerRequestInterface
    {
        $uri = (new UriFactory())
            ->createUri()
            ->withScheme('https')
            ->withHost('localhost')
            ->withPath('/');

        /** @var resource $stream */
        $stream = fopen('php://temp', 'r+');

        fwrite($stream, json_encode($payload));
        rewind($stream);

        $body = new Stream($stream);
        $headers = new Headers(['Content-Type' => 'application/json']);

        return new SlimRequest(
            method: 'POST',
            uri: $uri,
            headers: $headers,
            cookies: [],
            serverParams: [],
            body: $body
        );
    }
}
