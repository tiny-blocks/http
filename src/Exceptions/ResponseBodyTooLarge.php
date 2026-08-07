<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use RuntimeException;

/**
 * Raised when a response body exceeds the configured byte ceiling before it is decoded.
 *
 * <p>The ceiling bounds the PHP string the response body is materialized into. Without it, a
 * hostile or misbehaving upstream can exhaust the process memory with a single oversized
 * payload. The body is never decoded when the ceiling is crossed.</p>
 */
final class ResponseBodyTooLarge extends RuntimeException implements HttpException
{
    private const string REASON_TEMPLATE = 'Response body exceeds the maximum of %d bytes.';

    private function __construct(int $maxBytes)
    {
        $template = ResponseBodyTooLarge::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $maxBytes));
    }

    /**
     * Creates a ResponseBodyTooLarge from the byte ceiling that was crossed.
     *
     * @param int $maxBytes The maximum number of bytes the response body may carry.
     * @return ResponseBodyTooLarge The composed exception describing the crossed ceiling.
     */
    public static function of(int $maxBytes): ResponseBodyTooLarge
    {
        return new ResponseBodyTooLarge(maxBytes: $maxBytes);
    }
}
