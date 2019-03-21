<?php
/**
 * The Offdev Project
 *
 * Offdev/Csv - Reads, parses and validates CSV files using streams
 *
 * @author      Pascal Severin <pascal@offdev.net>
 * @copyright   Copyright (c) 2018, Pascal Severin
 * @license     Apache License 2.0
 */
declare(strict_types=1);

namespace Offdev\Tests;

use Offdev\Csv\Stream;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamTest
 * @package Offdev\Tests
 */
final class StreamTest extends TestCase
{
    public function testFactoryWorksWithString(): void
    {
        $stream = stream('some stream');
        $this->assertEquals('some stream', (string)$stream);
    }

    public function testFactoryWorksWithResource(): void
    {
        $stream = stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $this->assertEquals(229, strlen((string)$stream));
    }

    public function testFactoryWorksWithStreamInterface(): void
    {
        $stream = stream('lol');
        $stream = stream($stream);
        $this->assertEquals('lol', (string)$stream);
    }

    public function testFactoryWorksWithStringableObject(): void
    {
        $obj = new class {
            public function __toString()
            {
                return 'magic objects are fun!';
            }
        };
        $stream = stream($obj);
        $this->assertEquals('magic objects are fun!', (string)$stream);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid resource type: object
     */
    public function testFactoryWithInvalidObjectThrows(): void
    {
        $obj = new \stdClass();
        stream($obj);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid resource type: integer
     */
    public function testFactoryThrowsWithInvalidResource(): void
    {
        stream(1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Stream must be a resource
     */
    public function testInstantiationThrowsIfNoResourceWasGiven(): void
    {
        new Stream(1);
    }

    public function testDetachedStreamReturnsEmptryString(): void
    {
        $stream = stream('LOL');
        if ($stream instanceof Stream) {
            $stream->__destruct();
        }
        $this->assertEquals('', (string)$stream);
    }

    public function testDetachedStreamHasNoSize(): void
    {
        $stream = stream('LOL');
        $stream->close();
        $this->assertEquals(0, $stream->getSize());
    }

    public function testStreamReturnsCorrectSize(): void
    {
        $stream = stream('LOL');
        $this->assertEquals(3, $stream->getSize());
    }

    public function testTellWorks(): void
    {
        $stream = stream('LOL');
        $this->assertEquals(0, $stream->tell());
        $string = (string)$stream;
        $this->assertEquals(3, $stream->tell());
    }

    public function testEofWorks(): void
    {
        $stream = stream('LOL');
        $this->assertFalse($stream->eof());
        $string = (string)$stream;
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekableWorks(): void
    {
        $stream = stream('LOL');
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsReadableWorks(): void
    {
        $stream = stream('LOL');
        $this->assertTrue($stream->isReadable());
    }

    public function testIsWritableWorks(): void
    {
        $stream = stream('LOL');
        $this->assertTrue($stream->isWritable());
    }

    public function testRewindWorks(): void
    {
        $stream = stream('LOL');
        $this->assertEquals(0, $stream->tell());
        $string = (string)$stream;
        $this->assertEquals(3, $stream->tell());
        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
    }

    public function testWritingWorks(): void
    {
        $stream = stream('LOL');
        $stream->seek(0, SEEK_END);
        $stream->write('-rofl');
        $this->assertEquals('LOL-rofl', (string)$stream);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream is no writable!
     */
    public function testNonWritableStreamThrowsOnWrite(): void
    {
        $stream = stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $stream->seek(0, SEEK_END);
        $stream->write('-rofl');
    }

    public function testGetMetaDataWorks(): void
    {
        $stream = stream('hahahaha');
        $meta = $stream->getMetadata();
        $this->assertArrayHasKey('wrapper_type', $meta);
        $this->assertArrayHasKey('stream_type', $meta);
        $this->assertArrayHasKey('mode', $meta);
        $this->assertArrayHasKey('unread_bytes', $meta);
        $this->assertArrayHasKey('seekable', $meta);
        $this->assertArrayHasKey('uri', $meta);
    }

    public function testGetMetaDataWithKeyWorks(): void
    {
        $stream = stream('hahahaha');
        $this->assertEquals('TEMP', $stream->getMetadata('stream_type'));
    }

    public function testReadWorks(): void
    {
        $stream = stream('hahahaha');
        $this->assertEquals('hahahaha', $stream->read(8));
    }

    public function testReadUnreadableReturnsFalse(): void
    {
        $stream = stream();
        $this->assertEquals(false, $stream->read(1));
    }

    public function testReadOnNonReadableReturnsEmptyString(): void
    {
        $stream = stream(fopen(__DIR__ . '/data/samples.csv', 'a'));
        $this->assertEmpty($stream->read(1));
    }

    public function testDetachedStreamNotReadable(): void
    {
        $stream = stream('lol');
        $res = $stream->detach();
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->tell());
        $this->assertEquals('resource', gettype($res));
    }

    public function testWriteEmptyStringReturnsZeroBytesWritten(): void
    {
        $stream = stream('lol');
        $bytes = $stream->write('');
        $this->assertEquals(0, $bytes);
    }
}
