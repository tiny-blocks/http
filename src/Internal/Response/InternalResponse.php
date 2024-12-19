<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Response\Stream\StreamFactory;

final readonly class InternalResponse implements ResponseInterface
{
    private function __construct(
        private StreamInterface $body,
        private Code $code,
        private ResponseHeaders $headers,
        private ProtocolVersion $protocolVersion
    ) {
    }

    public static function createWithBody(mixed $body, Code $code, Headers ...$headers): ResponseInterface
    {
        return new InternalResponse(
            body: StreamFactory::fromBody(body: $body)->write(),
            code: $code,
            headers: ResponseHeaders::fromOrDefault(...$headers),
            protocolVersion: ProtocolVersion::default()
        );
    }

    public static function createWithoutBody(Code $code, Headers ...$headers): ResponseInterface
    {
        return new InternalResponse(
            body: StreamFactory::fromEmptyBody()->write(),
            code: $code,
            headers: ResponseHeaders::fromOrDefault(...$headers),
            protocolVersion: ProtocolVersion::default()
        );
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return new InternalResponse(
            body: $body,
            code: $this->code,
            headers: $this->headers,
            protocolVersion: $this->protocolVersion
        );
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        throw new BadMethodCall(method: __FUNCTION__);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $headers = ResponseHeaders::fromOrDefault(
            $this->headers,
            ResponseHeaders::fromNameAndValue(name: $name, value: $value)
        );

        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $headers,
            protocolVersion: $this->protocolVersion
        );
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $headers = $this->headers->removeByName(name: $name);

        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $headers,
            protocolVersion: $this->protocolVersion
        );
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $headers = ResponseHeaders::fromNameAndValue(name: $name, value: $value);

        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $headers,
            protocolVersion: $this->protocolVersion
        );
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $protocolVersion = ProtocolVersion::from(version: $version);

        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $this->headers,
            protocolVersion: $protocolVersion
        );
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->hasHeader(name: $name);
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function getHeader(string $name): array
    {
        return $this->headers->getByName(name: $name);
    }

    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    public function getStatusCode(): int
    {
        return $this->code->value;
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->headers->getByName(name: $name));
    }

    public function getReasonPhrase(): string
    {
        return $this->code->message();
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion->version;
    }
}
