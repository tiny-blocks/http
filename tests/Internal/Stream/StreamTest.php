<?php

namespace TinyBlocks\Http\Internal\Stream;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Exceptions\MissingResourceStream;
use TinyBlocks\Http\Internal\Exceptions\NonReadableStream;
use TinyBlocks\Http\Internal\Exceptions\NonSeekableStream;
use TinyBlocks\Http\Internal\Exceptions\NonWritableStream;

class StreamTest extends TestCase
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

    public function testCloseDetachesResource(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $stream->close();

        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse(is_resource($this->resource));
    }

    public function testCloseWithoutResource(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $stream->close();
        $stream->close();

        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse(is_resource($this->resource));
    }

    public function testEofReturnsTrueAtEndOfStream(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello');
        $eofBeforeRead = $stream->eof();
        $stream->read(length: 5);

        self::assertTrue($stream->eof());
        self::assertTrue($stream->isReadable());
        self::assertFalse($eofBeforeRead);
    }

    public function testGetMetadata(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $actual = $stream->getMetadata();
        $expected = StreamMetaData::from(data: stream_get_meta_data($this->resource))->toArray();

        self::assertNull($stream->getMetadata(key: ''));
        self::assertEquals($expected, $actual);
        self::assertEquals($expected['mode'], $stream->getMetadata(key: 'mode'));
    }

    public function testSeekMovesCursorPosition(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello, world!');
        $stream->seek(offset: 7);
        $tellAfterFirstSeek = $stream->tell();
        $stream->seek(offset: 0, whence: SEEK_END);

        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertEquals(7, $tellAfterFirstSeek);
        self::assertEquals(13, $stream->tell());
    }

    public function testRewindResetsCursorPosition(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $stream->write(string: 'Hello, world!');
        $stream->seek(offset: 7);
        $stream->rewind();

        self::assertEquals(0, $stream->tell());
    }

    public function testGetSizeReturnsCorrectSize(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $sizeBeforeWrite = $stream->getSize();
        $stream->write(string: 'Hello, world!');

        self::assertEquals(0, $sizeBeforeWrite);
        self::assertEquals(13, $stream->getSize());
    }

    public function testGetSizeReturnsNullWhenWithoutResource(): void
    {
        $stream = Stream::from(resource: $this->resource);
        $stream->close();

        self::assertNull($stream->getSize());
    }

    public function testExceptionWhenMissingResourceStreamOnTell(): void
    {
        $stream = Stream::from(resource: $this->resource);

        self::expectException(MissingResourceStream::class);
        self::expectExceptionMessage('No resource available.');

        $stream->close();
        $stream->tell();
    }

    public function testExceptionWhenNonSeekableStream(): void
    {
        $stream = Stream::from(resource: $this->resource);

        self::expectException(NonSeekableStream::class);
        self::expectExceptionMessage('Stream is not seekable.');

        $stream->close();
        $stream->seek(offset: 1);
    }

    public function testExceptionWhenNonWritableStream(): void
    {
        $stream = Stream::from(resource: fopen($this->temporary, 'r'));

        self::expectException(NonWritableStream::class);
        self::expectExceptionMessage('Stream is not writable.');

        $stream->write(string: 'Hello, world!');
    }

    public function testExceptionWhenNonReadableStreamOnRead(): void
    {
        $stream = Stream::from(resource: fopen($this->temporary, 'w'));

        self::expectException(NonReadableStream::class);
        self::expectExceptionMessage('Stream is not readable.');

        $stream->read(length: 13);
    }

    public function testExceptionWhenNonReadableStreamOnGetContents(): void
    {
        $stream = Stream::from(resource: fopen($this->temporary, 'w'));

        self::expectException(NonReadableStream::class);
        self::expectExceptionMessage('Stream is not readable.');

        $stream->getContents();
    }
}
