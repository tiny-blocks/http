<?php

namespace TinyBlocks\Http;

/**
 * HTTP defines a set of request methods to indicate the desired action to be performed for a given resource.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
 */
enum HttpMethod: string
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
}
