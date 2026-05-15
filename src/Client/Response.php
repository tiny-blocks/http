<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Client;

use Psr\Http\Message\ResponseInterface;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\SynthesizedResponseHasNoRaw;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Internal\Shared\Body;

final readonly class Response
{
    private function __construct(
        private ?ResponseInterface $psr,
        private Body $body,
        private Code $code,
        private Headers $headers
    ) {
    }

    public static function from(ResponseInterface $response): Response
    {
        return new Response(
            psr: $response,
            body: Body::fromResponse(response: $response),
            code: Code::from($response->getStatusCode()),
            headers: Headers::fromMessage(message: $response)
        );
    }

    public static function with(Code $code, ?array $body = null, array $headers = []): Response
    {
        return new Response(
            psr: null,
            body: Body::fromArray(data: $body ?? []),
            code: $code,
            headers: Headers::fromArray(entries: $headers)
        );
    }

    public function raw(): ResponseInterface
    {
        if (is_null($this->psr)) {
            throw SynthesizedResponseHasNoRaw::create();
        }

        return $this->psr;
    }

    public function code(): Code
    {
        return $this->code;
    }

    public function body(): Body
    {
        return $this->body;
    }

    public function isError(): bool
    {
        return $this->code->isError();
    }

    public function headers(): Headers
    {
        return $this->headers;
    }

    public function isSuccess(): bool
    {
        return $this->code->isSuccess();
    }
}
