<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use Closure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 decorator that sets headers on every outbound request, resolving each value at send time.
 *
 * <p>Each header value is a deferred resolution invoked per request, so values that change between
 * requests (a correlation identifier, a rotating token) are always current. A resolved value
 * replaces any header of the same name already present on the outbound request. A header whose
 * value resolves to an empty string is omitted, and the request keeps whatever it already
 * carried under that name.</p>
 */
final readonly class HeaderSettingClient implements ClientInterface
{
    /**
     * @param ClientInterface $client The underlying client to delegate sends to.
     * @param array<string, Closure(): string> $headerValues The header names, each mapped to the
     *                                                       resolution producing its value.
     */
    private function __construct(private ClientInterface $client, private array $headerValues)
    {
    }

    /**
     * Creates a HeaderSettingClient from a PSR-18 client and the headers to set on each request.
     *
     * @param ClientInterface $client The underlying client to delegate sends to.
     * @param array<string, Closure(): string> $headerValues The header names, each mapped to the
     *                                                       resolution producing its value.
     * @return HeaderSettingClient The created instance.
     */
    public static function with(ClientInterface $client, array $headerValues): HeaderSettingClient
    {
        return new HeaderSettingClient(client: $client, headerValues: $headerValues);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($this->headerValues as $name => $resolveValue) {
            $value = $resolveValue();

            if ($value === '') {
                continue;
            }

            $request = $request->withHeader($name, $value);
        }

        return $this->client->sendRequest($request);
    }
}
