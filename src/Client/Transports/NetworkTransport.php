<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client\Transports;

use JsonException;
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
        private StreamFactoryInterface $streamFactory,
        private RequestFactoryInterface $requestFactory
    ) {
    }

    public static function with(
        ClientInterface $client,
        StreamFactoryInterface $streamFactory,
        RequestFactoryInterface $requestFactory
    ): NetworkTransport {
        return new NetworkTransport(
            client: $client,
            streamFactory: $streamFactory,
            requestFactory: $requestFactory
        );
    }

    public function send(Request $request): Response
    {
        $psrRequest = $this->requestFactory->createRequest(
            method: $request->method->value,
            uri: $request->url
        );

        $psrRequest = $request->headers->applyTo(message: $psrRequest);

        if (!is_null($request->body)) {
            try {
                $encoded = json_encode(
                    $request->body,
                    self::JSON_FLAGS,
                    self::MAX_JSON_DEPTH
                );
            } catch (JsonException $exception) {
                throw HttpRequestInvalid::fromJsonError(request: $request, exception: $exception);
            }

            $stream = $this->streamFactory->createStream(content: $encoded);
            $psrRequest = $psrRequest->withBody($stream);
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
