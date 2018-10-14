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

use Offdev\Csv\Item;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidatorTest
 * @package Offdev\Tests
 */
final class ItemTest extends TestCase
{
    /**
     * Makes sure the response doesn't get modified whe no middleware ws given
     */
    public function testDefaultItemIsValid(): void
    {
        $item = new Item();
        $this->assertTrue($item->isValid());
    }

    /**
     * Makes sure the response doesn't get modified whe no middleware ws given
     */
    public function testSetterWorks(): void
    {
        $item = new Item();
        $item->setIsValid(false);
        $this->assertFalse($item->isValid());
    }
}
