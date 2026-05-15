<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Internal\Server\Stream;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use TinyBlocks\Http\Internal\Server\Stream\StreamFactory;

final class StreamFactoryTest extends TestCase
{
    public function testWriteWhenBodyGivenThenStreamCarriesContent(): void
    {
        /** @Given an HTTP response body */
        $body = 'This is a test body';

        /** @And the StreamFactory is created with the given body */
        $streamFactory = StreamFactory::fromBody(body: $body);

        /** @When the body is written to the stream */
        $stream = $streamFactory->write();

        /** @Then the stream contains the written content */
        self::assertSame($body, $stream->getContents());
    }

    public function testFromStreamWhenSeekableThenRewindsBeforeAndAfterReading(): void
    {
        /** @Given a seekable stream observed via a stub */
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);

        /** @And rewind call counter */
        $rewindCalls = 0;
        $stream->method('rewind')->willReturnCallback(
            static function () use (&$rewindCalls): void {
                $rewindCalls++;
            }
        );
        $stream->method('getContents')->willReturnCallback(
            static function () use (&$rewindCalls): string {
                self::assertSame(1, $rewindCalls);
                return 'body';
            }
        );

        /** @When a StreamFactory is created from the stream */
        StreamFactory::fromStream(stream: $stream);

        /** @Then it rewinds twice (before and after reading) */
        self::assertSame(2, $rewindCalls);
    }

    public function testFromStreamWhenNotSeekableThenDoesNotRewind(): void
    {
        /** @Given a non-seekable stream observed via a stub */
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(false);

        /** @And rewind call counter */
        $rewindCalls = 0;
        $stream->method('rewind')->willReturnCallback(
            static function () use (&$rewindCalls): void {
                $rewindCalls++;
            }
        );
        $stream->method('getContents')->willReturnCallback(
            static function () use (&$rewindCalls): string {
                self::assertSame(0, $rewindCalls);
                return 'body';
            }
        );

        /** @When a StreamFactory is created from the stream */
        StreamFactory::fromStream(stream: $stream);

        /** @Then it does not rewind */
        self::assertSame(0, $rewindCalls);
    }

    public function testFromBodyWhenStringGivenThenCarriesBodyVerbatim(): void
    {
        /** @Given a real seekable stream */
        $stream = new Psr17Factory()->createStream('payload');

        /** @When wrapping it through StreamFactory::fromStream */
        $factory = StreamFactory::fromStream(stream: $stream);

        /** @Then the factory's content matches the stream */
        self::assertSame('payload', $factory->content());
    }
}
