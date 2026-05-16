<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use TinyBlocks\Http\Internal\Client\Exceptions\PathContainsControlChars;
use TinyBlocks\Http\Internal\Client\Exceptions\PathContainsScheme;

final class Url
{
    private const string CONTROL_CHARS_PATTERN = '/[\x00-\x1F\x7F]/';
    private const string SCHEME_OR_PROTOCOL_RELATIVE_PATTERN = '#^(?://|\\\\\\\\|[a-z][a-z0-9+.-]*:)#i';

    public static function compose(string $path, ?array $query, string $baseUrl): string
    {
        if (preg_match(Url::SCHEME_OR_PROTOCOL_RELATIVE_PATTERN, $path) === 1) {
            throw PathContainsScheme::create(path: $path);
        }

        if (preg_match(Url::CONTROL_CHARS_PATTERN, $path) === 1) {
            throw PathContainsControlChars::create(path: $path);
        }

        $joinTemplate = '%s/%s';
        $absolute = $baseUrl === ''
            ? $path
            : sprintf($joinTemplate, rtrim($baseUrl, '/'), ltrim($path, '/'));

        if (is_null($query) || $query === []) {
            return $absolute;
        }

        $queryTemplate = '%s?%s';

        return sprintf($queryTemplate, $absolute, http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }
}
