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

namespace Offdev\Csv;

use Psr\Http\Message\StreamInterface;

/**
 * Class Stream
 * @package Offdev\CsvParser
 */
final class Stream implements StreamInterface
{
    /** @var resource|null */
    private $stream;

    /** @var array */
    private $meta = [];

    /** @var int */
    private $size = 0;

    /** @var bool */
    private $isReadable = false;

    /** @var bool */
    private $isWritable = false;

    /** @var bool */
    private $isSeekable = false;

    /**
     * Hash of readable and writable stream types (blatantly stolen from Guzzle :>)
     *
     * @var array
     */
    private static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    /**
     * @param string|resource|object $resource
     * @return StreamInterface
     * @throws \InvalidArgumentException
     */
    public static function factory($resource = '')
    {
        $type = gettype($resource);
        if ($type == 'string') {
            $stream = fopen('php://temp', 'r+');
            if ($resource != '') {
                fwrite($stream, $resource);
                fseek($stream, 0);
            }
            return new self($stream);
        }
        if ($type == 'resource') {
            return new self($resource);
        }
        if ($resource instanceof StreamInterface) {
            return $resource;
        }
        if ($type == 'object' && method_exists($resource, '__toString')) {
            return self::factory((string)$resource);
        }

        throw new \InvalidArgumentException('Invalid resource type: '.$type);
    }

    /**
     * Stream constructor.
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }
        $this->attach($stream);
    }

    /**
     * Make sure to clean up behind us :)
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if (!$this->stream) {
            return '';
        }
        $this->seek(0);
        return $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    /**
     * Connects the underlying resources to the stream.
     *
     * After the stream has been attached, it will be in a usable state.
     *
     * @param resource $stream
     */
    private function attach($stream)
    {
        $this->stream = $stream;
        $this->meta = stream_get_meta_data($this->stream);
        $this->isSeekable = $this->meta['seekable'];
        $this->isReadable = isset(self::$readWriteHash['read'][$this->meta['mode']]);
        $this->isWritable = isset(self::$readWriteHash['write'][$this->meta['mode']]);
        $stats = fstat($this->stream);
        $this->size = isset($stats['size']) ? $stats['size'] : null;
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $this->meta = [];
        $result = $this->stream;
        $this->stream = null;
        $this->size = 0;
        $this->isReadable = $this->isWritable = $this->isSeekable = false;
        return $result;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        return $this->stream ? ftell($this->stream) : false;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->isSeekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->isSeekable) {
            fseek($this->stream, $offset, $whence);
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->isWritable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        $bytesWritten = $this->isWritable ? fwrite($this->stream, $string) : false;
        if (false === $bytesWritten) {
            throw new \RuntimeException('Stream is no writable!');
        }

        return $bytesWritten;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->isReadable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable) {
            return '';
        }
        return fread($this->stream, $length);
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        return $this->stream
            ? stream_get_contents($this->stream)
            : '';
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        return !is_null($key)
            ? ($this->meta[$key] ?? null)
            : $this->meta;
    }
}
