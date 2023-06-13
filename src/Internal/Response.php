<?php

namespace TinyBlocks\Http\Internal;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

final class Response implements ResponseInterface
{
    private function __construct(
        private readonly HttpCode $code,
        private readonly StreamInterface $body,
        private readonly array $headers
    ) {
    }

    public static function from(HttpCode $code, mixed $data, array $headers): ResponseInterface
    {
        if (empty($headers)) {
            $headers[] = ['Content-Type' => 'application/json'];
        }

        return new Response(code: $code, body: StreamFactory::from(data: $data), headers: $headers);
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        throw new BadMethodCall(method: __METHOD__);
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        throw new BadMethodCall(method: __METHOD__);
    }

    public function withHeader(string $name, mixed $value): MessageInterface
    {
        throw new BadMethodCall(method: __METHOD__);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        throw new BadMethodCall(method: __METHOD__);
    }

    public function withAddedHeader(string $name, mixed $value): MessageInterface
    {
        throw new BadMethodCall(method: __METHOD__);
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        throw new BadMethodCall(method: __METHOD__);
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader(name: $name));
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->code->value;
    }

    public function getReasonPhrase(): string
    {
        return $this->code->message();
    }
}
