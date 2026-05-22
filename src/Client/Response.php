<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Body;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\SynthesizedResponseHasNoRaw;
use TinyBlocks\Http\Headers;

final readonly class Response
{
    private function __construct(
        private ?ResponseInterface $psr,
        private Body $body,
        private Code $code,
        private Headers $headers
    ) {
    }

    /**
     * Creates a Response from a PSR-7 response.
     *
     * @param ResponseInterface $response The underlying PSR-7 response.
     * @return Response A wrapped Response carrying the PSR-7 message.
     */
    public static function from(ResponseInterface $response): Response
    {
        return new Response(
            psr: $response,
            body: Body::fromResponse(response: $response),
            code: Code::from($response->getStatusCode()),
            headers: Headers::fromMessage(message: $response)
        );
    }

    /**
     * Synthesizes a response from a status code and an optional body and headers.
     *
     * @param Code $code The HTTP status code carried by the synthesized response.
     * @param array<string, mixed>|null $body The response body as an associative array, or null for an empty body.
     * @param Headers|null $headers The response headers, or null for an empty headers instance.
     * @return Response A synthesized response without a backing PSR-7 message.
     */
    public static function with(Code $code, ?array $body = null, ?Headers $headers = null): Response
    {
        return new Response(
            psr: null,
            body: Body::fromArray(data: $body ?? []),
            code: $code,
            headers: $headers ?? Headers::fromArray(entries: [])
        );
    }

    /**
     * Returns the status code.
     *
     * @return Code The status code carried by the response.
     */
    public function code(): Code
    {
        return $this->code;
    }

    /**
     * Returns the body.
     *
     * @return Body The parsed body of the response.
     */
    public function body(): Body
    {
        return $this->body;
    }

    /**
     * Returns the headers.
     *
     * @return Headers The headers carried by the response.
     */
    public function headers(): Headers
    {
        return $this->headers;
    }

    /**
     * Tells whether the status code denotes an error response.
     *
     * @return bool True when the code falls in the 4xx or 5xx range, otherwise false.
     */
    public function isError(): bool
    {
        return $this->code->isError();
    }

    /**
     * Tells whether the status code denotes a successful response.
     *
     * @return bool True when the code falls in the 2xx range, otherwise false.
     */
    public function isSuccess(): bool
    {
        return $this->code->isSuccess();
    }

    /**
     * Returns the underlying PSR-7 response.
     *
     * @return ResponseInterface The original PSR-7 response wrapped by this instance.
     * @throws SynthesizedResponseHasNoRaw If the response was synthesized via {@see Response::with()} and
     *                                      has no backing PSR-7 message.
     */
    public function raw(): ResponseInterface
    {
        if (is_null($this->psr)) {
            throw SynthesizedResponseHasNoRaw::create();
        }

        return $this->psr;
    }
}
