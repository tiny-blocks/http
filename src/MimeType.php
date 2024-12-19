<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Enum representing common MIME types used in HTTP Content-Type and Accept headers.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept
 */
enum MimeType: string
{
    case TEXT_HTML = 'text/html';
    case TEXT_PLAIN = 'text/plain';
    case APPLICATION_PDF = 'application/pdf';
    case APPLICATION_JSON = 'application/json';
    case APPLICATION_OCTET_STREAM = 'application/octet-stream';
    case APPLICATION_FORM_URLENCODED = 'application/x-www-form-urlencoded';
}
