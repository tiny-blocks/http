<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Internal\Stream;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Internal\Stream\StreamFactory;

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
        self::assertSame($body, $stream->getContents());
    }

    public function testFromStreamShouldRewindBeforeAndAfterReadingWhenSeekable(): void
    {
        /** @Given a seekable stream */
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);

        /** @And rewind call counter */
        $rewindCalls = 0;

        /** @And rewind increments the counter */
        $stream->method('rewind')->willReturnCallback(
            static function () use (&$rewindCalls): void {
                $rewindCalls++;
            }
        );

        /** @And getContents must be called after the first rewind */
        $stream->method('getContents')->willReturnCallback(
            static function () use (&$rewindCalls): string {
                self::assertSame(1, $rewindCalls);
                return 'body';
            }
        );

        /** @When a StreamFactory is created from the stream */
        StreamFactory::fromStream(stream: $stream);

        /** @Then it must rewind twice (before and after reading) */
        self::assertSame(2, $rewindCalls);
    }

    public function testFromStreamShouldNotRewindWhenNotSeekable(): void
    {
        /** @Given a non-seekable stream */
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(false);

        /** @And rewind call counter */
        $rewindCalls = 0;

        /** @And rewind increments the counter */
        $stream->method('rewind')->willReturnCallback(
            static function () use (&$rewindCalls): void {
                $rewindCalls++;
            }
        );

        /** @And getContents must be called without any rewind */
        $stream->method('getContents')->willReturnCallback(
            static function () use (&$rewindCalls): string {
                self::assertSame(0, $rewindCalls);
                return 'body';
            }
        );

        /** @When a StreamFactory is created from the stream */
        StreamFactory::fromStream(stream: $stream);

        /** @Then it must not rewind */
        self::assertSame(0, $rewindCalls);
    }
}
