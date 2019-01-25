# Offdev/Csv

[![Latest Stable Version](https://img.shields.io/packagist/vpre/offdev/csv.svg?style=flat-square)](https://packagist.org/packages/offdev/csv)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://img.shields.io/travis/offdev/csv/master.svg?style=flat-square)](https://travis-ci.org/offdev/csv)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Mutation Score](https://badge.stryker-mutator.io/github.com/offdev/csv/master)](https://travis-ci.org/offdev/csv)
[![License](https://img.shields.io/github/license/offdev/csv.svg)](https://www.apache.org/licenses/LICENSE-2.0)

## Requirements
* PHP >= 7.2
* Composer

## Installation
```bash
$ composer require offdev/csv
```

## Introduction

This parser has been written, in order to parser big CSV files from almost any data source in an easy and convenient way. It also provides the possibility to validate each record from the CSV source.

In order for the parser to work, you need to feed it with data. This data will be represented as a stream. This allows us to handle huge amounts of data. The parser can also work with HTTP streams.

Please read further to see how it is used.

## Streams

In order to feed the parser with data, you need to give it a stream. A stream can be obtained in a different number of ways:

### Using resources
```php
use Offdev\Csv\Stream;

$stream = Stream::factory(fopen('/path/to/file.csv', 'r'));
```

### Using strings
```php
use Offdev\Csv\Stream;

$stream = Stream::factory('this string will be transformed to an in-memory stream');
```
Note: this method also works for any object which implements the ```__toString``` method.

### Using HTTP streams (see [PSR-7/Streams](https://www.php-fig.org/psr/psr-7/#13-streams))
```php
use GuzzleHttp\Client;
use Offdev\Csv\Stream;

$client = new Client();
$response = $client->get('http://httpbin.org/get');
$stream = Stream::factory($response->getBody());
```

### Convenience

If you want to quickly create a stream, you can use the provided helper function. You need to use composer's autoloader to be able to use this.

```php
// Recognizes files, and opens them in read mode
$fileStream = stream('/tmp/results.csv');

// Create from string
$stringStream = stream('stream content');

// From objcets, which implement the __toString method
class Example
{
    public function __toString(){
     return 'some example';
    }
}
$objectToStringStream = stream(new Example());
```

## Parser

### Basics
Once the parser has a stream to work with, we can start using it:
```php
use Offdev\Csv\Parser;

$parser = new Parser($stream);
while (!$parser->eof()) {
    $record = $parser->readLine();
    echo $record->get('header-column2').PHP_EOL;
}
```

The example above produces following output:

```
$ php example.php
row1-value2
row2-value2
```

For convenience, the parser can also be used as an iterator:

```php
$parser = new Parser($stream);
foreach ($parser as $index => $record) {
    echo $record->get('header-column2').PHP_EOL;
} 
```

This will produce the same output as the example above.

### Options

The parser accepts a number of options. The parser accepts options in an array, which is passed a a second argument to the constructor:
```php
$parser = new Parser($stream, [
    Parser::OPTION_DELIMITER => ';'
]);
```

Full list of options:

| Option                                | Value Type | Default Value | Description                                                                                                           |
|---------------------------------------|------------|:-------------:|-----------------------------------------------------------------------------------------------------------------------|
| ```Parser::OPTION_BUFSIZE```          | \<integer> | ```1024```    | Defines the size of the buffer which is used when reading streams.                                                    |
| ```Parser::OPTION_HEADER```           | \<boolean> | ```true```    | Tells the parser if the CSV contains a header. This header will be used as keys for the records read from the stream. | 
| ```Parser::OPTION_DELIMITER```        | \<string>  | ```','```     | Defines the delimiter used in the CSV file to mark columns.                                                           | 
| ```Parser::OPTION_STRING_ENCLOSURE``` | \<string>  | ```'"'```     | Defines the delimiter used for strings.                                                                               | 
| ```Parser::OPTION_ESCAPE_CHAR```      | \<string>  | ```'\\'```    | Defines the character used to escape other control characters.                                                        | 
| ```Parser::OPTION_EOL```              | \<string>  | ```"\n"```    | Defines the line ending used in the CSV file. Unix files mostly use ```\n``` while windows mostly uses ```\r\n```.    | 
| ```Parser::OPTION_THROWS```           | \<boolean> | ```true```    | Tells the parser to throw an exception when an invalid records was found in the stream.                               | 

## Processor

For better usability and separation of concerns, the parser accepts a processor, which will receive any parsed records from the stream. Records are represented as [Laravel collections](https://laravel.com/docs/5.6/collections).

If a validator was assigned to the parser, valid and invalid records will be passed to the respective methods. If no validator was given, all records will be passed to the ```parseRecord``` method. Empty lines will always be ignored.

Example processor:

```php
namespace MyCompany\ProjectX\Processors;

use Offdev\Csv\Item;
use Offdev\Csv\ProcessorInterface;

class MyProcessor implements ProcessorInterface
{
    public function processRecord(Item $record): void
    {
        // No header in CSV, use numeric index
        echo "Got item: ".$record->get(1).PHP_EOL;
    }
    
    public function processInvalidRecord(Item $record): void
    {
        $this->processRecord($record);
    }
    
    public function eof(): void
    {
        echo "---EOF---".PHP_EOL;
    }
}
```

Usage:

```php
use MyCompany\ProjectX\Processors\MyProcessor;
use Offdev\Csv\Parser;
use Offdev\Csv\Stream;

$stream = Stream::factory("1;John\n2;Lisa\n3;Robert");
$parser = new Parser($stream, [
    Parser::OPTION_DELIMITER => ';',
    Parser::OPTION_HEADER => false
]);
$parser->setProcessor(new MyProcessor());
$parser->run();
```
The example above produces following output:

```
$ php example.php
Got item: John
Got item: Lisa
Got item: Robert
---EOF---
```

## Validator

Now, most of the times, we want to make sure the data contained in the CSV is in a given format. This package uses the Laravel validation package in order to provide a rule engine for the content of the CSV. A full list of all rules can be found [here](https://laravel.com/docs/5.7/validation#available-validation-rules).

Usage:

```php
use Offdev\Csv\Parser;
use Offdev\Csv\Stream;
use Offdev\Csv\Validator;

try {
    $stream = Stream::factory("id,name\n1,John\n2,Lisa\nNaN,Robert");
    $parser = new Parser($stream);
    $parser->setValidator(new Validator([
        'id' => 'required|numeric',
        'name' => 'required|string|min:3'
    ]));
    $parser->run();
    echo "CSV is valid!".PHP_EOL;
} catch (\Exception $e) {
    echo "CSV is invalid!".PHP_EOL;
}
```

## Code quality

First, make sure to install the dependencies by running ```composer install```. You also need to make sure to have xdebug activated in order for PHPUnit to generate the code coverage.

**PHP Code Sniffer**
```
$ ./vendor/bin/phpcs --colors --standard=PSR2 -v src/ tests/
Registering sniffs in the PSR2 standard... DONE (42 sniffs registered)
Creating file list... DONE (10 files in queue)
Changing into directory /Users/pascal/devel/csv-parser/src
Processing Validator.php [PHP => 436 tokens in 74 lines]... DONE in 46ms (0 errors, 0 warnings)
Processing Parser.php [PHP => 2125 tokens in 312 lines]... DONE in 140ms (0 errors, 0 warnings)
Processing Stream.php [PHP => 2248 tokens in 344 lines]... DONE in 116ms (0 errors, 0 warnings)
Processing ParserInterface.php [PHP => 552 tokens in 115 lines]... DONE in 27ms (0 errors, 0 warnings)
Processing ProcessorInterface.php [PHP => 168 tokens in 36 lines]... DONE in 21ms (0 errors, 0 warnings)
Changing into directory /Users/pascal/devel/csv-parser/tests
Processing ParserTest.php [PHP => 1797 tokens in 214 lines]... DONE in 149ms (0 errors, 0 warnings)
Processing TestProcessor.php [PHP => 427 tokens in 80 lines]... DONE in 31ms (0 errors, 0 warnings)
Processing ValidatorTest.php [PHP => 179 tokens in 33 lines]... DONE in 14ms (0 errors, 0 warnings)
Processing StreamTest.php [PHP => 1647 tokens in 217 lines]... DONE in 124ms (0 errors, 0 warnings)
Processing InvalidStream.php [PHP => 999 tokens in 210 lines]... DONE in 57ms (0 errors, 0 warnings)
```

**PHPUnit**
```
$ ./vendor/bin/phpunit
PHPUnit 7.4.0 by Sebastian Bergmann and contributors.

......................................                            38 / 38 (100%)

Time: 1.71 seconds, Memory: 8.00MB

OK (38 tests, 66 assertions)

Generating code coverage report in HTML format ... done


Code Coverage Report:
  2018-10-14 08:42:12

 Summary:
  Classes: 100.00% (3/3)
  Methods: 100.00% (36/36)
  Lines:   100.00% (150/150)

\Offdev\Csv::Offdev\Csv\Parser
  Methods: 100.00% (15/15)   Lines: 100.00% ( 76/ 76)
\Offdev\Csv::Offdev\Csv\Stream
  Methods: 100.00% (19/19)   Lines: 100.00% ( 67/ 67)
\Offdev\Csv::Offdev\Csv\Validator
  Methods: 100.00% ( 2/ 2)   Lines: 100.00% (  7/  7)
```

**Infection**
```
$ ./vendor/bin/infection
You are running Infection with xdebug enabled.
    ____      ____          __  _
   /  _/___  / __/__  _____/ /_(_)___  ____
   / // __ \/ /_/ _ \/ ___/ __/ / __ \/ __ \
 _/ // / / / __/  __/ /__/ /_/ / /_/ / / / /
/___/_/ /_/_/  \___/\___/\__/_/\____/_/ /_/

Running initial test suite...

PHPUnit version: 7.4.0

   44 [============================] 2 secsProcessing source code files: 0/5

Generate mutants...

Processing source code files: 5/5
Creating mutated files and processes: 89/89
.: killed, M: escaped, S: uncovered, E: fatal error, T: timed out

E.E...M..EEEM.EE.EEE.E.......E....................   (50 / 89)
...........E...........................              (89 / 89)

89 mutations were generated:
      74 mutants were killed
       0 mutants were not covered by tests
       2 covered mutants were not detected
      13 errors were encountered
       0 time outs were encountered

Metrics:
         Mutation Score Indicator (MSI): 97%
         Mutation Code Coverage: 100%
         Covered Code MSI: 97%

Please note that some mutants will inevitably be harmless (i.e. false positives).
Dashboard report has not been sent: it is not a Travis CI

Time: 17s. Memory: 14.00MB
```

### License
[Apache-2.0](https://www.apache.org/licenses/LICENSE-2.0)