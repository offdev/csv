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

/**
 * Interface ProcessorInterface
 * @package Offdev\CsvParser
 */
interface ProcessorInterface
{
    /**
     * @param Item $record
     */
    public function processRecord(Item $record): void;

    /**
     * @param Item $record
     */
    public function processInvalidRecord(Item $record): void;

    /**
     * Called when the parser hit the end of the stream.
     */
    public function eof();
}
