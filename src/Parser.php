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

use Illuminate\Support\Collection;
use Psr\Http\Message\StreamInterface;

/**
 * A CSV Parser
 *
 * This parser reads a stream, validates and processes contained CSV data.
 *
 * @package Offdev\CsvParser
 */
class Parser implements ParserInterface
{
    /** @var string */
    private $delimiter = ',';

    /** @var string */
    private $lineEnding = "\n";

    /** @var StreamInterface */
    private $stream;

    /** @var string */
    private $buffer = '';

    /** @var int */
    private $bufferSize;

    /** @var Validator */
    private $validator;

    /** @var ProcessorInterface */
    private $processor;

    /** @var string[] */
    private $header = [];

    /** @var bool */
    private $hasHeader = true;

    /** @var bool */
    private $throws = true;

    /**
     * Wraps around a stream, and acceps options
     *
     * @param StreamInterface $stream
     * @param array $options
     */
    public function __construct(StreamInterface $stream, array $options = [])
    {
        $this->parseOptions($options);
        $this->stream = $stream;
        $this->buffer = '';
    }

    /**
     * Assigns a validator
     *
     * Makes sure that the input is validated before further manipulating it.
     *
     * @param Validator $validator
     * @return ParserInterface
     */
    public function setValidator(Validator $validator): ParserInterface
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Assign a processor
     *
     * Provides an easy to use feedback loop for CSV records.
     *
     * @param ProcessorInterface $processor
     * @return ParserInterface
     */
    public function setProcessor(ProcessorInterface $processor): ParserInterface
    {
        $this->processor = $processor;
        return $this;
    }

    /**
     * Rewinds the underlying stream
     *
     * @return ParserInterface
     */
    public function rewind(): ParserInterface
    {
        $this->stream->rewind();
        return $this;
    }

    /**
     * Reads a line from the steam
     *
     * Reads a string from the stream, up to the defined delimiter. Empty lines
     * are skipped. Headers are read automatically, if tge option was set.
     *
     * @return Collection|false
     */
    public function readLine()
    {
        if ($this->hasHeader && empty($this->header)) {
            $line = $this->readLineFromBuffer();
            if (empty($line)) {
                return $this->readLine();
            }
            $header = explode($this->delimiter, $line);
            $this->header = $header;
        }
        return $this->parseLine($this->readLineFromBuffer());
    }

    /**
     * Check for the end of the stream
     *
     * @return bool
     */
    public function eof(): bool
    {
        return $this->stream->eof() && empty($this->buffer);
    }

    /**
     * Runs the parser
     *
     * Runs the parser on the underlying stream. If a validator has been assigned,
     * the parser will validate each record, except for the header. The records
     * found in the stream are then passed to a processor, if one has been assigned.
     */
    public function run(): void
    {
        if (!$this->stream->isReadable() || !$this->stream->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable/readable!');
        }

        $this->rewind();

        do {
            $this->readLine();
        } while (!$this->eof());
        if ($this->processor instanceof ProcessorInterface) {
            $this->processor->eof();
        }
    }

    /**
     * Fills up the buffer
     *
     * Fills the buffer up to the given size, in order to read from it.
     */
    private function buffer(): void
    {
        $remaining = $this->bufferSize - strlen($this->buffer);
        if ($remaining > 0 && !$this->stream->eof()) {
            $this->buffer .= $this->stream->read($remaining);
        }
    }

    /**
     * Reads a line from the buffer
     *
     * @return string|false
     */
    private function readLineFromBuffer()
    {
        $this->buffer();
        $pos = mb_strpos($this->buffer, $this->lineEnding);
        if ($pos !== false || (!empty($this->buffer) && $this->stream->eof())) {
            $line = ($pos !== false) ? mb_substr($this->buffer, 0, $pos) : $this->buffer;
            $this->buffer = ($pos !== false) ? mb_substr($this->buffer, $pos+strlen($this->delimiter)) : '';
            return $line;
        }

        if (!empty($this->buffer)) {
            throw new \RuntimeException('Buffer size too small! No line ending found within buffer.');
        }

        return false;
    }

    /**
     * Parses a line
     *
     * Transforms a string to a collection, using headers if available. The collection
     * is then passed to the underlying processor for further manipulation.
     *
     * @param string $line
     * @return Collection|false
     */
    private function parseLine($line)
    {
        $record = $this->getCollection($line);
        if (!$record) {
            return false;
        }

        if ($this->validator instanceof Validator) {
            if ($this->validator->isValid($record)) {
                $this->parseSuccess($record);
            } else {
                $this->parseFailed($record);
            }
        } else {
            $this->parseSuccess($record);
        }

        return $record;
    }

    /**
     * Builds a collection from a string
     *
     * @param string|false $line
     * @return Collection|false
     */
    private function getCollection($line)
    {
        if (!is_string($line)) {
            return $line;
        }
        $result = explode($this->delimiter, $line);
        if ($this->hasHeader) {
            if (count($this->header) != count($result)) {
                if ($this->throws) {
                    throw new \RuntimeException('Invalid item count in stream!');
                }
                return $this->getCollection($this->readLineFromBuffer());
            }
            $record = collect(array_combine($this->header, $result));
        } else {
            $record = collect($result);
        }

        return $record;
    }

    /**
     * Passes a result to the underlying processor
     *
     * Also passes failed results to the processor, if no validator was assigned.
     *
     * @param Collection $record
     */
    private function parseSuccess(Collection $record): void
    {
        if ($this->processor instanceof ProcessorInterface) {
            $this->processor->processRecord($record);
        }
    }

    /**
     * Passes a failed result to the underlying processor
     *
     * @param Collection $record
     */
    private function parseFailed(Collection $record): void
    {
        if ($this->throws) {
            throw new \RuntimeException('Invalid record found in stream!');
        }

        if ($this->processor instanceof ProcessorInterface) {
            $this->processor->processInvalidRecord($record);
        }
    }

    /**
     * Parses options
     *
     * @param array $options
     */
    private function parseOptions(array $options)
    {
        $this->bufferSize = $this->arrayGet($options, 'integer', self::OPTION_BUFSIZE, 1024);
        $this->delimiter = $this->arrayGet($options, 'string', self::OPTION_DELIMITER, ',');
        $this->lineEnding = $this->arrayGet($options, 'string', self::OPTION_EOL, "\n");
        $this->hasHeader = $this->arrayGet($options, 'boolean', self::OPTION_HEADER, true);
        $this->throws = $this->arrayGet($options, 'boolean', self::OPTION_THROWS, true);
    }

    /**
     * @param array $array
     * @param string $type
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function arrayGet(array $array, string $type, string $key, $default = null)
    {
        if (isset($array[$key]) && gettype($array[$key]) == $type) {
            return $array[$key];
        }

        return $default;
    }
}
