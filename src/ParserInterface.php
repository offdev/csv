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
 * Interface ParserInterface
 * @package Offdev\CsvParser
 */
interface ParserInterface
{
    /**
     * Controls the buffer size when reading the stream
     */
    const OPTION_BUFSIZE = 'buffer_size';

    /**
     * Tells the parser if the CSV file has a header
     */
    const OPTION_HEADER = 'has_header';

    /**
     * Defines the column delimiter
     */
    const OPTION_DELIMITER = 'column_delimiter';

    /**
     * Controls the line ending character.
     */
    const OPTION_EOL = 'end_of_line';

    /**
     * Throws exceptions on validation errors
     */
    const OPTION_THROWS = 'throws';

    /**
     * ParserInterface constructor.
     *
     * Wraps around a stream, and accepts options
     *
     * @param StreamInterface $stream
     * @param array $options
     */
    public function __construct(StreamInterface $stream, array $options = []);

    /**
     * Assigns a validator
     *
     * Assigns a validator to the parser, in order to validate
     * CSV records while reading the stream.
     *
     * @param Validator $validator
     * @return ParserInterface
     */
    public function setValidator(Validator $validator): ParserInterface;

    /**
     * Assigns a processor
     *
     * Assings a processor, which will be called whenever the
     * parser reads a line from the stream.
     *
     * @param ProcessorInterface $processor
     * @return ParserInterface
     */
    public function setProcessor(ProcessorInterface $processor): ParserInterface;

    /**
     * Rewinds the stream
     *
     * Moves the cursor to the beginning of the stream.
     *
     * @return ParserInterface
     */
    public function rewind(): ParserInterface;

    /**
     * Reads a line from the stream
     *
     * Reads a line from the stream, and puts it in a collection
     * for further manipulation.
     *
     * @return Collection|false
     */
    public function readLine();

    /**
     * Checks for the end of file
     *
     * Returns true when we reached the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool;

    /**
     * Parses the whole stream
     *
     * Parses the stream, using the provided processor to further
     * manipulate CSV records found in the stream.
     */
    public function run(): void;
}
