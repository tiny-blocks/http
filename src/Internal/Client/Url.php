<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

use InvalidArgumentException;

final readonly class Url
{
    private const string CONTROL_REASON = 'Path must not contain control characters.';
    private const string CONTROL_CHARS_PATTERN = '/[\x00-\x1F\x7F]/';
    private const string SCHEME_REASON_TEMPLATE = 'Path "%s" must not contain a scheme or be protocol-relative.';
    private const string SCHEME_OR_PROTOCOL_RELATIVE_PATTERN = '#^(?://|\\\\\\\\|[a-z][a-z0-9+.-]*:)#i';

    public static function compose(string $path, ?array $query, string $baseUrl): string
    {
        if (preg_match(self::SCHEME_OR_PROTOCOL_RELATIVE_PATTERN, $path) === 1) {
            throw new InvalidArgumentException(sprintf(self::SCHEME_REASON_TEMPLATE, $path));
        }

        if (preg_match(self::CONTROL_CHARS_PATTERN, $path) === 1) {
            throw new InvalidArgumentException(self::CONTROL_REASON);
        }

        $absolute = $baseUrl === ''
            ? $path
            : sprintf('%s/%s', rtrim($baseUrl, '/'), ltrim($path, '/'));

        if (is_null($query) || $query === []) {
            return $absolute;
        }

        return sprintf('%s?%s', $absolute, http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }
}
