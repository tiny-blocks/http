<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\MalformedPath;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Internal\Client\Exceptions\PathContainsControlChars;
use TinyBlocks\Http\Internal\Client\Exceptions\PathContainsScheme;

final readonly class RequestResolver
{
    private const array JSON_DEFAULTS = [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json'
    ];

    private function __construct(private string $baseUrl)
    {
    }

    public static function withBaseUrl(string $baseUrl): RequestResolver
    {
        return new RequestResolver(baseUrl: $baseUrl);
    }

    public function resolve(Request $request): Request
    {
        try {
            $url = Url::compose(
                path: $request->url(),
                baseUrl: $this->baseUrl,
                queryParameters: $request->queryParameters()
            );
        } catch (PathContainsScheme | PathContainsControlChars $exception) {
            throw MalformedPath::fromRequest(request: $request, previous: $exception);
        }

        return $request
            ->withUrl(url: $url)
            ->withMergedHeaders(defaults: Headers::fromArray(entries: RequestResolver::JSON_DEFAULTS))
            ->withQueryParameters(queryParameters: null);
    }
}
