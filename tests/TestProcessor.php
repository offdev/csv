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

use Illuminate\Support\Collection;
use Offdev\Csv\Item;
use Offdev\Csv\ProcessorInterface;

/**
 * Class TestProcessor
 * @package Offdev\Tests
 */
final class TestProcessor implements ProcessorInterface
{
    /** @var array */
    private $validRecords = [];

    /** @var array */
    private $invalidRecords = [];

    /** @var bool */
    private $eof = false;

    /**
     * @return Collection
     */
    public function getValidRecords(): Collection
    {
        return new Collection($this->validRecords);
    }

    /**
     * @return Collection
     */
    public function getInvalidRecords(): Collection
    {
        return new Collection($this->invalidRecords);
    }

    /**
     * @return bool
     */
    public function getEof(): bool
    {
        return $this->eof;
    }

    /**
     * @param Item $record
     */
    public function processRecord(Item $record): void
    {
        $this->validRecords[] = $record;
    }

    /**
     * @param Item $record
     */
    public function processInvalidRecord(Item $record): void
    {
        $this->invalidRecords[] = $record;
    }

    /**
     * Called when the parser hit the end of the stream.
     */
    public function eof()
    {
        $this->eof = true;
    }
}
