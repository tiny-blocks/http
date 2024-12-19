<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Response\Stream;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class StreamFactoryTest extends TestCase
{
    public function testWriteShouldRewindStream(): void
    {
        /** @Given an HTTP response body */
        $body = 'This is a test body';

        /** @And the StreamFactory is created with the given body */
        $streamFactory = StreamFactory::fromBody(body: $body);

        /** @When the body is written to the stream */
        $stream = $streamFactory->write();

        /** @Then the stream should contain the written content */
        self::assertInstanceOf(StreamInterface::class, $stream);
        self::assertSame($body, $stream->getContents());
    }
}
