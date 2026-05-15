<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Transports;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;

final readonly class NetworkTransport implements Transport
{
    private const int JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
    private const int MAX_JSON_DEPTH = 64;

    private function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface&StreamFactoryInterface $factory
    ) {
    }

    public static function with(
        ClientInterface $client,
        RequestFactoryInterface&StreamFactoryInterface $factory
    ): NetworkTransport {
        return new NetworkTransport(client: $client, factory: $factory);
    }

    public function send(Request $request): Response
    {
        $psrRequest = $this->factory->createRequest(
            method: $request->method->value,
            uri: $request->url
        );

        $psrRequest = $request->headers->applyTo(message: $psrRequest);

        if (!is_null($request->body)) {
            $encoded = json_encode($request->body, self::JSON_FLAGS, self::MAX_JSON_DEPTH);
            $psrRequest = $psrRequest->withBody(body: $this->factory->createStream(content: $encoded));
        }

        try {
            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (NetworkExceptionInterface $exception) {
            throw HttpNetworkFailed::fromClientException(request: $request, exception: $exception);
        } catch (RequestExceptionInterface $exception) {
            throw HttpRequestInvalid::fromClientException(request: $request, exception: $exception);
        } catch (ClientExceptionInterface $exception) {
            throw HttpRequestFailed::fromClientException(request: $request, exception: $exception);
        }

        return Response::from(response: $psrResponse);
    }
}
