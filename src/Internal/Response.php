<?php

namespace TinyBlocks\Http\Internal;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\HttpCode;
use TinyBlocks\Http\HttpContentType;
use TinyBlocks\Http\HttpHeaders;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

final readonly class Response implements ResponseInterface
{
    private function __construct(private HttpCode $code, private StreamInterface $body, private HttpHeaders $headers)
    {
    }

    public static function from(HttpCode $code, mixed $data, ?HttpHeaders $headers): ResponseInterface
    {
        if (is_null($headers) || $headers->hasNoHeaders()) {
            $headers = HttpHeaders::build()
                ->addFromCode(code: $code)
                ->addFromContentType(header: HttpContentType::APPLICATION_JSON);
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
        return $this->headers->toArray();
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->hasHeader(key: $name);
    }

    public function getHeader(string $name): array
    {
        return $this->headers->getHeader(key: $name);
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->headers->getHeader(key: $name));
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
