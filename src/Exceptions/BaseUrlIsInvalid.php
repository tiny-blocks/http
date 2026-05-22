<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a base URL passed to <code>Http::with()</code> or <code>HttpBuilder::withBaseUrl()</code>
 * fails validation.
 *
 * Accepted forms are the empty string and absolute URLs beginning with <code>http://</code> or
 * <code>https://</code>. Protocol-relative URLs, non-HTTP schemes, and URLs carrying control
 * characters are rejected.
 */
final class BaseUrlIsInvalid extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Base URL <%s> is invalid. Only empty string, http://, and https:// '
        . 'base URLs are accepted.';

    private function __construct(string $url)
    {
        $template = BaseUrlIsInvalid::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $url));
    }

    /**
     * Creates a BaseUrlIsInvalid signaling that the given URL does not satisfy the accepted forms.
     *
     * @param string $url The offending base URL.
     * @return BaseUrlIsInvalid The composed exception describing the invalid base URL.
     */
    public static function for(string $url): BaseUrlIsInvalid
    {
        return new BaseUrlIsInvalid(url: $url);
    }
}
