<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Response;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Headerable;
use TinyBlocks\Http\Internal\Server\Stream\StreamFactory;

final readonly class InternalResponse implements ResponseInterface
{
    private function __construct(
        private StreamInterface $body,
        private Code $code,
        private ResponseHeaders $headers,
        private ProtocolVersion $protocolVersion,
        private ?string $customReasonPhrase
    ) {
    }

    public static function createWithBody(mixed $body, Code $code, Headerable ...$headers): ResponseInterface
    {
        return new InternalResponse(
            body: StreamFactory::fromBody(body: $body)->write(),
            code: $code,
            headers: ResponseHeaders::fromOrDefault(...$headers),
            protocolVersion: ProtocolVersion::default(),
            customReasonPhrase: null
        );
    }

    public static function createWithoutBody(Code $code, Headerable ...$headers): ResponseInterface
    {
        return new InternalResponse(
            body: StreamFactory::fromEmptyBody()->write(),
            code: $code,
            headers: ResponseHeaders::fromOrDefault(...$headers),
            protocolVersion: ProtocolVersion::default(),
            customReasonPhrase: null
        );
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return new InternalResponse(
            body: $body,
            code: $this->code,
            headers: $this->headers,
            protocolVersion: $this->protocolVersion,
            customReasonPhrase: $this->customReasonPhrase
        );
    }

    public function getHeader(string $name): array
    {
        return $this->headers->getByName(name: $name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->hasHeader(name: $name);
    }

    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    public function withHeader(string $name, mixed $value): MessageInterface
    {
        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $this->headers->withReplaced(name: $name, value: $value),
            protocolVersion: $this->protocolVersion,
            customReasonPhrase: $this->customReasonPhrase
        );
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return new InternalResponse(
            body: $this->body,
            code: Code::from($code),
            headers: $this->headers,
            protocolVersion: $this->protocolVersion,
            customReasonPhrase: $reasonPhrase !== '' ? $reasonPhrase : null
        );
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader(name: $name));
    }

    public function getStatusCode(): int
    {
        return $this->code->value;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $this->headers->removeByName(name: $name),
            protocolVersion: $this->protocolVersion,
            customReasonPhrase: $this->customReasonPhrase
        );
    }

    public function getReasonPhrase(): string
    {
        return $this->customReasonPhrase ?? $this->code->message();
    }

    public function withAddedHeader(string $name, mixed $value): MessageInterface
    {
        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $this->headers->withAdded(name: $name, value: $value),
            protocolVersion: $this->protocolVersion,
            customReasonPhrase: $this->customReasonPhrase
        );
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion->version;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $protocolVersion = ProtocolVersion::from(version: $version);

        return new InternalResponse(
            body: $this->body,
            code: $this->code,
            headers: $this->headers,
            protocolVersion: $protocolVersion,
            customReasonPhrase: $this->customReasonPhrase
        );
    }
}
