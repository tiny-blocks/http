<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Request;

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

final readonly class Body
{
    private function __construct(private array $data)
    {
    }

    public static function from(ServerRequestInterface $request): Body
    {
        $body = $request->getBody();
        $streamFactory = StreamFactory::fromStream(stream: $body);

        if (!$streamFactory->isEmptyContent()) {
            return new Body(data: json_decode($streamFactory->content(), true));
        }

        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody)) {
            return new Body(data: $parsedBody);
        }

        return new Body(data: []);
    }

    public function get(string $key): Attribute
    {
        $value = ($this->data[$key] ?? null);

        return Attribute::from(value: $value);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
