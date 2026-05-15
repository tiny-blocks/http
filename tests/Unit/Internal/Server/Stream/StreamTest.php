<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Internal\Server\Stream;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Server\Exceptions\InvalidResource;
use TinyBlocks\Http\Internal\Server\Exceptions\MissingResourceStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonReadableStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonSeekableStream;
use TinyBlocks\Http\Internal\Server\Exceptions\NonWritableStream;
use TinyBlocks\Http\Internal\Server\Stream\Stream;
use TinyBlocks\Http\Internal\Server\Stream\StreamMetaData;

final class StreamTest extends TestCase
{
    private mixed $resource;
    private ?string $temporary;

    protected function setUp(): void
    {
        $this->temporary = tempnam(sys_get_temp_dir(), 'test');
        $this->resource = fopen($this->temporary, 'wb+');
    }

    protected function tearDown(): void
    {
        if (!empty($this->temporary) && file_exists($this->temporary)) {
            unlink($this->temporary);
        }
    }

    public function testGetMetadataWhenInvokedThenReturnsResourceMetadata(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When retrieving metadata */
        $actual = $stream->getMetadata();

        /** @Then the metadata matches the underlying resource's metadata */
        $expected = StreamMetaData::from(data: stream_get_meta_data($this->resource))->toArray();

        self::assertSame($expected['uri'], $actual['uri']);
        self::assertSame($expected['mode'], $actual['mode']);
        self::assertSame($expected['seekable'], $actual['seekable']);
        self::assertSame($expected['streamType'], $actual['streamType']);
    }

    public function testCloseWhenAlreadyClosedThenIsNoOp(): void
    {
        /** @Given a stream that has already been closed */
        $stream = Stream::from(resource: $this->resource);
        $stream->close();

        /** @When closing the stream again */
        $stream->close();

        /** @Then the stream remains detached */
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse(is_resource($this->resource));
    }

    public function testCloseWhenInvokedThenDetachesResource(): void
    {
        /** @Given a stream resource */
        $stream = Stream::from(resource: $this->resource);

        /** @When the stream is closed */
        $stream->close();

        /** @Then the resource is detached */
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse(is_resource($this->resource));
    }

    public function testSeekWhenInvokedThenMovesCursorPosition(): void
    {
        /** @Given a stream with data */
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello, world!');

        /** @When seeking to a specific position */
        $stream->seek(offset: 7);
        $tellAfterFirstSeek = $stream->tell();
        $stream->seek(offset: 0, whence: SEEK_END);

        /** @Then the cursor moves correctly */
        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertSame(7, $tellAfterFirstSeek);
        self::assertSame(13, $stream->tell());
    }

    public function testGetSizeWhenWritesPerformedThenReflectsContentLength(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When writing to the stream */
        $sizeBeforeWrite = $stream->getSize();
        $stream->write(string: 'Hello, world!');

        /** @Then the size reflects the bytes written */
        self::assertSame(0, $sizeBeforeWrite);
        self::assertSame(13, $stream->getSize());
    }

    public function testIsWritableWhenCreateModeGivenThenReturnsTrue(): void
    {
        /** @Given a file that does not exist */
        unlink($this->temporary);

        /** @When opening the stream in create mode ('x') */
        $stream = Stream::from(resource: fopen($this->temporary, 'x'));

        /** @Then the stream is writable */
        self::assertTrue($stream->isWritable());
    }

    #[DataProvider('modesDataProvider')]
    public function testIsWritableWhenModeGivenThenMatchesExpectation(string $mode, bool $expected): void
    {
        /** @Given a stream opened in a specific mode */
        $stream = Stream::from(resource: fopen('php://memory', $mode));

        /** @Then the writable flag matches the expectation */
        self::assertSame($expected, $stream->isWritable());
    }

    public function testRewindWhenInvokedThenResetsCursorPosition(): void
    {
        /** @Given a stream with data */
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello, world!');

        /** @When rewinding the stream */
        $stream->seek(offset: 7);
        $stream->rewind();

        /** @Then the cursor returns to the beginning */
        self::assertSame(0, $stream->tell());
    }

    public function testEofWhenEndReachedThenReturnsTrue(): void
    {
        /** @Given a stream with data */
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello');

        /** @When reading every byte */
        $eofBeforeRead = $stream->eof();
        $stream->read(length: 5);

        /** @Then EOF reports true at the end */
        self::assertTrue($stream->eof());
        self::assertTrue($stream->isReadable());
        self::assertFalse($eofBeforeRead);
    }

    public function testGetMetadataWhenUnknownKeyGivenThenReturnsNull(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When retrieving metadata for an unknown key */
        $actual = $stream->getMetadata(key: 'UNKNOWN');

        /** @Then the result is null */
        self::assertNull($actual);
    }

    public function testToStringWhenInvokedThenReturnsFullContent(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When writing and converting the stream to string */
        $stream->write(string: 'Hello, world!');

        /** @Then the content matches the written data */
        self::assertSame('Hello, world!', (string)$stream);
    }

    public function testGetSizeWhenStreamClosedThenReturnsNull(): void
    {
        /** @Given a stream that has been closed */
        $stream = Stream::from(resource: $this->resource);
        $stream->close();

        /** @Then getSize returns null */
        self::assertNull($stream->getSize());
    }

    public function testIsSeekableWhenResourceClosedExternallyThenReturnsFalse(): void
    {
        /** @Given a stream whose underlying resource was closed outside the stream API */
        $resource = fopen('php://memory', 'w+');
        $stream = Stream::from(resource: $resource);
        fclose($resource);

        /** @When checking if the stream is seekable */
        $actual = $stream->isSeekable();

        /** @Then it returns false because the resource is no longer valid */
        self::assertFalse($actual);
    }

    public function testSeekWhenStreamClosedThenThrowsNonSeekableStream(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @Then NonSeekableStream is thrown */
        self::expectException(NonSeekableStream::class);
        self::expectExceptionMessage('Stream is not seekable.');

        /** @When attempting to seek on a closed stream */
        $stream->close();
        $stream->seek(offset: 1);
    }

    public function testWriteWhenStreamReadOnlyThenThrowsNonWritableStream(): void
    {
        /** @Given a read-only stream */
        $stream = Stream::from(resource: fopen($this->temporary, 'r'));

        /** @Then NonWritableStream is thrown */
        self::expectException(NonWritableStream::class);
        self::expectExceptionMessage('Stream is not writable.');

        /** @When attempting to write to the stream */
        $stream->write(string: 'Hello, world!');
    }

    public function testReadWhenStreamWriteOnlyThenThrowsNonReadableStream(): void
    {
        /** @Given a write-only stream */
        $stream = Stream::from(resource: fopen($this->temporary, 'w'));

        /** @Then NonReadableStream is thrown */
        self::expectException(NonReadableStream::class);
        self::expectExceptionMessage('Stream is not readable.');

        /** @When attempting to read from the stream */
        $stream->read(length: 13);
    }

    public function testFromWhenInvalidResourceGivenThenThrowsInvalidResource(): void
    {
        /** @Given an invalid resource */
        $resource = 'not_a_resource';

        /** @Then InvalidResource is thrown */
        $this->expectException(InvalidResource::class);
        $this->expectExceptionMessage('The provided value is not a valid resource.');

        /** @When calling from() with an invalid resource */
        Stream::from(resource: $resource);
    }

    public function testTellWhenStreamClosedThenThrowsMissingResourceStream(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @Then MissingResourceStream is thrown */
        self::expectException(MissingResourceStream::class);
        self::expectExceptionMessage('No resource available.');

        /** @When attempting to call tell on a closed stream */
        $stream->close();
        $stream->tell();
    }

    public function testGetContentsWhenStreamWriteOnlyThenThrowsNonReadableStream(): void
    {
        /** @Given a write-only stream */
        $stream = Stream::from(resource: fopen($this->temporary, 'w'));

        /** @Then NonReadableStream is thrown */
        self::expectException(NonReadableStream::class);
        self::expectExceptionMessage('Stream is not readable.');

        /** @When attempting to get contents of the stream */
        $stream->getContents();
    }

    public static function modesDataProvider(): array
    {
        return [
            'Read mode (r)'              => ['mode' => 'r', 'expected' => false],
            'Write mode (w)'             => ['mode' => 'w', 'expected' => true],
            'Append mode (a)'            => ['mode' => 'a', 'expected' => true],
            'Mixed read/write mode (r+)' => ['mode' => 'r+', 'expected' => true]
        ];
    }
}
