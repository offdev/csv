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
use Offdev\Csv\Parser;
use Offdev\Csv\Stream;
use Offdev\Csv\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Class ParserTest
 * @package Offdev\Tests
 */
final class ParserTest extends TestCase
{
    public function testReadLineWorks(): void
    {
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream, [
            Parser::OPTION_THROWS => false,
            Parser::OPTION_BUFSIZE => 225
        ]);
        /** @var Collection[] $result */
        $result = [];
        do {
            $result[] = $parser->readLine();
        } while (!$parser->eof());

        $this->assertEquals('invalid', $result[3]->get('column1'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid item count in stream!
     */
    public function testReadLineThrowsOnInvaldRecords(): void
    {
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream);
        $result = [];
        do {
            $result[] = $parser->readLine();
        } while (!$parser->eof());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Buffer size too small! No line ending found within buffer.
     */
    public function testTooTinyBufferSizeThrowsOnRead(): void
    {
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream, [Parser::OPTION_BUFSIZE => 1]);
        $parser->readLine();
    }

    public function testParserRewindsStreamWorks(): void
    {
        $stream = Stream::factory('some-stream');
        $parser = new Parser($stream);
        $parser->readLine();
        $this->assertEquals(11, $stream->tell());
        $parser = $parser->rewind();
        $this->assertEquals(0, $stream->tell());
        $this->assertInstanceOf(Parser::class, $parser);
    }

    public function testReadLineAfterEofReturnsFalse(): void
    {
        $stream = Stream::factory('some-stream');
        $parser = new Parser($stream, [Parser::OPTION_HEADER => false]);
        $content = $parser->readLine()->get(0);
        $this->assertEquals('some-stream', $content);
        $content = $parser->readLine();
        $this->assertFalse($content);
    }

    public function testParserValidatesCsv()
    {
        $processor = new TestProcessor();
        $validator = new Validator([
            'column1' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column2' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column3' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
        ]);
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream, [
            Parser::OPTION_THROWS => false,
            Parser::OPTION_BUFSIZE => 45
        ]);
        $parser->setProcessor($processor);
        $parser->setValidator($validator);
        $parser->run();

        $this->assertEquals(1, $processor->getInvalidRecords()->count());
        $this->assertEquals(4, $processor->getValidRecords()->count());
        $this->assertTrue($processor->getEof());
    }

    public function testParserIgnoresEmptyLines()
    {
        $processor = new TestProcessor();
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream, [
            Parser::OPTION_THROWS => false,
            Parser::OPTION_BUFSIZE => 45
        ]);
        $parser->setProcessor($processor);
        $parser->run();

        $this->assertEquals(5, $processor->getValidRecords()->count());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid record found in stream!
     */
    public function testParserValidatesCsvThrows()
    {
        $processor = new TestProcessor();
        $validator = new Validator([
            'column1' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column2' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column3' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
        ]);
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream);
        $parser->setProcessor($processor);
        $parser->setValidator($validator);
        $parser->run();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Stream is not seekable/readable!
     */
    public function testParserThrowsOnInvalidStream()
    {
        $stream = Stream::factory(new InvalidStream());
        $parser = new Parser($stream);
        $parser->run();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid record found in stream!
     */
    public function testRunRewindsStream()
    {
        $processor = new TestProcessor();
        $validator = new Validator([
            'column1' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column2' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column3' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
        ]);
        $stream = new Stream(fopen(__DIR__ . '/data/samples.csv', 'r'));
        $parser = new Parser($stream);
        $parser->setProcessor($processor);
        $parser->setValidator($validator);
        $parser->run();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid item count in stream!
     */
    public function testThrowsOnFirstInvalidLine()
    {
        $processor = new TestProcessor();
        $validator = new Validator([
            'column1' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column2' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column3' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
        ]);
        $stream = new Stream(fopen(__DIR__.'/data/other-samples.csv', 'r'));
        $parser = new Parser($stream);
        $parser->setProcessor($processor);
        $parser->setValidator($validator);
        $parser->run();
    }

    public function testProcessesHighlyInvalidFile()
    {
        $processor = new TestProcessor();
        $validator = new Validator([
            'column1' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column2' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
            'column3' => 'required|string|min:14|max:14|regex:/^row-\d\/column-\d$/i',
        ]);
        $stream = new Stream(fopen(__DIR__.'/data/other-samples.csv', 'r'));
        $parser = new Parser($stream, [
            Parser::OPTION_THROWS => false
        ]);
        $stream->seek(0, SEEK_END);
        $parser->setProcessor($processor);
        $parser->setValidator($validator);
        $parser->run();

        $this->assertEquals(2, $processor->getValidRecords()->count());
        $this->assertEquals('row-1/column-3', $processor->getValidRecords()->get(0)->get('column3'));
        $this->assertEquals(1, $processor->getInvalidRecords()->count());
    }
}
