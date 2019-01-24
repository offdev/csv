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

    /** @var string */
    private $stringEnclosure = '"';

    /** @var string */
    private $escapeChar = '\\';

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

    /** @var Item|false */
    private $currentLine = false;

    /** @var int */
    private $iteratorIndex = 0;

    /**
     * Wraps around a stream, and accepts options
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
        $this->currentLine = false;
        return $this;
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return Item Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        if (!$this->currentLine) {
            $this->currentLine = $this->readLine();
        }
        return $this->currentLine;
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->currentLine = $this->readLine();
        $this->iteratorIndex++;
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return int scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->iteratorIndex;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return !$this->eof() || ($this->currentLine instanceof Item);
    }

    /**
     * Reads a line from the steam
     *
     * Reads a string from the stream, up to the defined delimiter. Empty lines
     * are skipped. Headers are read automatically, if tge option was set.
     *
     * @return Item|false
     */
    public function readLine()
    {
        $this->parseHeader();
        do {
            $line = $this->readLineFromBuffer();
        } while (empty($line) && !$this->eof());
        return $this->parseLine($line);
    }

    /**
     * Check for a header
     *
     * Searches for a header, ignoring any blank lines.
     */
    private function parseHeader()
    {
        if ($this->hasHeader && empty($this->header)) {
            do {
                $line = $this->readLineFromBuffer();
            } while (empty($line) && !$this->eof());
            $header = explode($this->delimiter, $line);
            $this->header = $header;
        }
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
     * @return Item|false
     */
    private function parseLine($line)
    {
        $record = $this->getItem($line);
        if (!$record) {
            return false;
        }

        if ($this->validator instanceof Validator) {
            if ($this->validator->isValid($record)) {
                $this->parseSuccess($record);
            } else {
                $record->setIsValid(false);
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
     * @return Item|false
     */
    private function getItem($line)
    {
        if (!is_string($line)) {
            return $line;
        }

        $result = str_getcsv($line, $this->delimiter, $this->stringEnclosure, $this->escapeChar);
        foreach ($result as $i => $r) {
            $result[$i] = $this->unescape($r);
        }
        if ($this->hasHeader) {
            if (count($this->header) != count($result)) {
                if ($this->throws) {
                    throw new \RuntimeException('Invalid item count in stream!');
                }
                return $this->getItem($this->readLineFromBuffer());
            }
            $record = new Item(array_combine($this->header, $result));
        } else {
            $record = new Item($result);
        }

        return $record;
    }

    /**
     * @param string $str
     * @return null|string
     */
    private function unescape(?string $str = null): ?string
    {
        $escapeSequence = $this->escapeChar.$this->stringEnclosure;
        return str_replace($escapeSequence, $this->stringEnclosure, $str);
    }

    /**
     * Passes a result to the underlying processor
     *
     * Also passes failed results to the processor, if no validator was assigned.
     *
     * @param Item $record
     */
    private function parseSuccess(Item $record): void
    {
        if ($this->processor instanceof ProcessorInterface) {
            $this->processor->processRecord($record);
        }
    }

    /**
     * Passes a failed result to the underlying processor
     *
     * @param Item $record
     */
    private function parseFailed(Item $record): void
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
        $this->stringEnclosure = $this->arrayGet($options, 'string', self::OPTION_STRING_ENCLOSURE, '"');
        $this->escapeChar = $this->arrayGet($options, 'string', self::OPTION_ESCAPE_CHAR, '\\');
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
