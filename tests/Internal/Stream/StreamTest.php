<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Stream;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Exceptions\InvalidResource;
use TinyBlocks\Http\Internal\Exceptions\MissingResourceStream;
use TinyBlocks\Http\Internal\Exceptions\NonReadableStream;
use TinyBlocks\Http\Internal\Exceptions\NonSeekableStream;
use TinyBlocks\Http\Internal\Exceptions\NonWritableStream;

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

    public function testGetMetadata(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When retrieving metadata */
        $actual = $stream->getMetadata();
        $expected = StreamMetaData::from(data: stream_get_meta_data($this->resource))->toArray();

        /** @Then the metadata should match the expected values */
        self::assertEquals($expected['uri'], $actual['uri']);
        self::assertEquals($expected['mode'], $actual['mode']);
        self::assertEquals($expected['seekable'], $actual['seekable']);
        self::assertEquals($expected['streamType'], $actual['streamType']);
    }

    public function testCloseWithoutResource(): void
    {
        /** @Given a stream that has already been closed */
        $stream = Stream::from(resource: $this->resource);
        $stream->close();

        /** @When closing the stream again */
        $stream->close();

        /** @Then the stream should remain closed and detached */
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse(is_resource($this->resource));
    }

    public function testCloseDetachesResource(): void
    {
        /** @Given a stream resource */
        $stream = Stream::from(resource: $this->resource);

        /** @When the stream is closed */
        $stream->close();

        /** @Then the stream should be detached and no longer readable, writable, or seekable */
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse(is_resource($this->resource));
    }

    public function testSeekMovesCursorPosition(): void
    {
        /** @Given a stream with data */
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello, world!');

        /** @When seeking to a specific position */
        $stream->seek(offset: 7);
        $tellAfterFirstSeek = $stream->tell();
        $stream->seek(offset: 0, whence: SEEK_END);

        /** @Then the cursor position should be updated correctly */
        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertEquals(7, $tellAfterFirstSeek);
        self::assertEquals(13, $stream->tell());
    }

    public function testGetSizeReturnsCorrectSize(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When writing to the stream */
        $sizeBeforeWrite = $stream->getSize();
        $stream->write(string: 'Hello, world!');

        /** @Then the size should be updated correctly */
        self::assertEquals(0, $sizeBeforeWrite);
        self::assertEquals(13, $stream->getSize());
    }

    public function testIsWritableForCreateMode(): void
    {
        /** @Given a file that does not exist */
        unlink($this->temporary);

        /** @When opening the stream in create mode ('x') */
        $stream = Stream::from(resource: fopen($this->temporary, 'x'));

        /** @Then the stream should be writable */
        self::assertTrue($stream->isWritable());
    }

    #[DataProvider('modesDataProvider')]
    public function testIsWritableForVariousModes(string $mode, bool $expected): void
    {
        /** @Given a stream opened in a specific mode */
        $stream = Stream::from(resource: fopen('php://memory', $mode));

        /** @Then check if the stream is writable based on the mode */
        self::assertEquals($expected, $stream->isWritable());
    }

    public function testRewindResetsCursorPosition(): void
    {
        /** @Given a stream with data */
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello, world!');

        /** @When rewinding the stream */
        $stream->seek(offset: 7);
        $stream->rewind();

        /** @Then the cursor position should be reset to the beginning */
        self::assertEquals(0, $stream->tell());
    }

    public function testEofReturnsTrueAtEndOfStream(): void
    {
        /** @Given a stream with data */
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello');

        /** @When reaching the end of the stream */
        $eofBeforeRead = $stream->eof();
        $stream->read(length: 5);

        /** @Then EOF should return true */
        self::assertTrue($stream->eof());
        self::assertTrue($stream->isReadable());
        self::assertFalse($eofBeforeRead);
    }

    public function testGetMetadataWhenKeyIsUnknown(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When retrieving metadata for an unknown key */
        $actual = $stream->getMetadata(key: 'UNKNOWN');

        /** @Then the result should be null */
        self::assertNull($actual);
    }

    public function testToStringRewindsStreamIfNotSeekable(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When writing and converting the stream to string */
        $stream->write(string: 'Hello, world!');

        /** @Then the content should match the written data */
        self::assertEquals('Hello, world!', (string)$stream);
    }

    public function testGetSizeReturnsNullWhenWithoutResource(): void
    {
        /** @Given a stream that has been closed */
        $stream = Stream::from(resource: $this->resource);
        $stream->close();

        /** @Then getSize should return null */
        self::assertNull($stream->getSize());
    }

    public function testExceptionWhenNonSeekableStream(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When attempting to seek on a closed stream */
        self::expectException(NonSeekableStream::class);
        self::expectExceptionMessage('Stream is not seekable.');

        $stream->close();
        $stream->seek(offset: 1);
    }

    public function testExceptionWhenNonWritableStream(): void
    {
        /** @Given a read-only stream */
        $stream = Stream::from(resource: fopen($this->temporary, 'r'));

        /** @When attempting to write to the stream */
        self::expectException(NonWritableStream::class);
        self::expectExceptionMessage('Stream is not writable.');

        $stream->write(string: 'Hello, world!');
    }

    public function testExceptionWhenNonReadableStreamOnRead(): void
    {
        /** @Given a write-only stream */
        $stream = Stream::from(resource: fopen($this->temporary, 'w'));

        /** @When attempting to read from the stream */
        self::expectException(NonReadableStream::class);
        self::expectExceptionMessage('Stream is not readable.');

        $stream->read(length: 13);
    }

    public function testExceptionWhenInvalidResourceProvided(): void
    {
        /** @Given an invalid resource (e.g., a string) */
        $resource = 'not_a_resource';

        /** @Then an InvalidResource exception should be thrown */
        $this->expectException(InvalidResource::class);
        $this->expectExceptionMessage('The provided value is not a valid resource.');

        /** @When calling the from method with an invalid resource */
        Stream::from(resource: $resource);
    }

    public function testExceptionWhenMissingResourceStreamOnTell(): void
    {
        /** @Given a stream */
        $stream = Stream::from(resource: $this->resource);

        /** @When attempting to call tell on a closed stream */
        self::expectException(MissingResourceStream::class);
        self::expectExceptionMessage('No resource available.');

        $stream->close();
        $stream->tell();
    }

    public function testExceptionWhenNonReadableStreamOnGetContents(): void
    {
        /** @Given a write-only stream */
        $stream = Stream::from(resource: fopen($this->temporary, 'w'));

        /** @When attempting to get contents of the stream */
        self::expectException(NonReadableStream::class);
        self::expectExceptionMessage('Stream is not readable.');

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
