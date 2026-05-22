<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * HTTP defines a set of request methods to indicate the desired action to be performed for a given resource.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
 */
enum Method: string
{
    case GET = 'GET';
    case PUT = 'PUT';
    case POST = 'POST';
    case HEAD = 'HEAD';
    case PATCH = 'PATCH';
    case TRACE = 'TRACE';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case CONNECT = 'CONNECT';

    /**
     * Tells whether the method is safe per RFC 9110 §9.2.1.
     *
     * Safe methods do not alter the state of the server. A request is safe when it is
     * read-only from the server's perspective. Safe methods are <code>GET</code>,
     * <code>HEAD</code>, <code>OPTIONS</code>, and <code>TRACE</code>.
     *
     * @see https://www.rfc-editor.org/rfc/rfc9110#section-9.2.1
     * @return bool True when the method is safe, otherwise false.
     */
    public function isSafe(): bool
    {
        return match ($this) {
            Method::GET,
            Method::HEAD,
            Method::OPTIONS,
            Method::TRACE => true,
            default       => false
        };
    }

    /**
     * Tells whether the method is idempotent per RFC 9110 §9.2.2.
     *
     * An idempotent method produces the same server state when applied one or more times.
     * All safe methods are idempotent. Additionally, <code>PUT</code> and <code>DELETE</code>
     * are idempotent but not safe.
     *
     * @see https://www.rfc-editor.org/rfc/rfc9110#section-9.2.2
     * @return bool True when the method is idempotent, otherwise false.
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            Method::GET,
            Method::PUT,
            Method::HEAD,
            Method::TRACE,
            Method::DELETE,
            Method::OPTIONS => true,
            default         => false
        };
    }
}
