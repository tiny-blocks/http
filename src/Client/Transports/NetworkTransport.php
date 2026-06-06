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

/**
 * PSR-18-backed {@see Transport} that dispatches each request through a real HTTP client.
 *
 * Builds PSR-7 request messages using a PSR-17 factory, serializes the body as JSON,
 * and maps PSR-18 client exceptions to typed library exceptions.
 */
final readonly class NetworkTransport implements Transport
{
    private const int JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

    private function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface&StreamFactoryInterface $factory
    ) {
    }

    /**
     * Creates a NetworkTransport backed by a PSR-18 client and a PSR-17 factory.
     *
     * @param ClientInterface $client The PSR-18 client that performs the actual network call.
     * @param RequestFactoryInterface&StreamFactoryInterface $factory The PSR-17 factory used to build the
     *                                                                PSR-7 request and body stream.
     * @return NetworkTransport A transport that dispatches each request through the given client.
     */
    public static function with(
        ClientInterface $client,
        RequestFactoryInterface&StreamFactoryInterface $factory
    ): NetworkTransport {
        return new NetworkTransport(client: $client, factory: $factory);
    }

    public function send(Request $request): Response
    {
        $psrRequest = $this->factory->createRequest($request->method()->value, $request->url());
        $psrRequest = $request->headers()->applyTo(message: $psrRequest);

        $body = $request->body();

        if (!is_null($body)) {
            $encoded = json_encode($body, NetworkTransport::JSON_FLAGS);
            $psrRequest = $psrRequest->withBody($this->factory->createStream($encoded));
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
