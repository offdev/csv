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

/**
 * Interface ProcessorInterface
 * @package Offdev\CsvParser
 */
interface ProcessorInterface
{
    /**
     * @param Collection $record
     */
    public function processRecord(Collection $record): void;

    /**
     * @param Collection $record
     */
    public function processInvalidRecord(Collection $record): void;

    /**
     * Called when the parser hit the end of the stream.
     */
    public function eof();
}
