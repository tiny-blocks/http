<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use InvalidArgumentException;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Exceptions\MalformedPath;
use TinyBlocks\Http\Headers;

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
            $url = Url::compose(path: $request->url, query: $request->query, baseUrl: $this->baseUrl);
        } catch (InvalidArgumentException) {
            throw MalformedPath::fromRequest(request: $request);
        }

        return $request
            ->withUrl(url: $url)
            ->withQuery(query: null)
            ->withMergedHeaders(defaults: new Headers(entries: self::JSON_DEFAULTS));
    }
}
