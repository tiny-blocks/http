<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use RuntimeException;
use TinyBlocks\Http\Body;
use TinyBlocks\Http\Code;

/**
 * Raised by {@see Response::orFail()} when an otherwise successful exchange returns a non-2xx status.
 *
 * <p>This is not a transport failure: the request was sent and a well-formed response came back. The
 * exception carries the {@see Code} and the decoded {@see Body} so the caller can branch on the status
 * and inspect the payload in one place, then map it to its own domain exception.</p>
 */
final class HttpResponseUnsuccessful extends RuntimeException implements HttpException
{
    private const string REASON_TEMPLATE = 'HTTP response returned a non-success status: %d %s.';

    private function __construct(private readonly Body $body, private readonly Code $statusCode)
    {
        $template = HttpResponseUnsuccessful::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $statusCode->value, $statusCode->message()));
    }

    /**
     * Creates an HttpResponseUnsuccessful from the response status code and decoded body.
     *
     * @param Code $code The non-success status code carried by the response.
     * @param Body $body The decoded response body, preserved for inspection by the caller.
     * @return HttpResponseUnsuccessful The composed exception describing the unsuccessful response.
     */
    public static function from(Code $code, Body $body): HttpResponseUnsuccessful
    {
        return new HttpResponseUnsuccessful(body: $body, statusCode: $code);
    }

    /**
     * Returns the decoded response body preserved for inspection.
     *
     * @return Body The decoded body of the unsuccessful response.
     */
    public function body(): Body
    {
        return $this->body;
    }

    /**
     * Returns the non-success status code carried by the response.
     *
     * @return Code The status code that triggered the failure.
     */
    public function code(): Code
    {
        return $this->statusCode;
    }
}
