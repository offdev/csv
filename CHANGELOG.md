# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- PHPStan (static code analysis)
- Mutation score badge

### Fixes
- Small documentation issues
- DocBlocks for several function parameters and private object properties
- stream helper function, when trying to open non-readable files
- Parsing of files in test cases (changed do/while to while)
- Index not resetting when iterating multiple times over the same parser
- Headers being parsed as values when iterating multiple times over the same parser

## [1.2.0] - 2019-01-24
### Added
- Possibility to define string delimiters
- Possibility to define the escape character

### Fixes
- Parsing strings enclosed in single or double quotes
- Issue with Iterator implementation, which sometimes ignored the last line in a csv

## [1.1.1] - 2018-10-15
### Fixes
- Errors in ```README.md```
- Typos in PHP docs
- Bug where invalid CSV item would be marked as valid
- Bug with multiple empty lines in stream

## [1.1.0] - 2018-10-15
### Added
- ```ParserInterface``` now implements the ```Iterator``` interface, for easy iterations over a CSV stream
- New class ```Item``` which extends Laravel's ```Collection``` class, and adds an ```isValid``` method, which returns a validation result if the parser was given a validator
- New convenience helper function to create streams: ```stream($input)```

## [1.0.1] - 2018-10-14
### Removed
- Support for PHP 7.1

## [1.0.0] - 2018-10-14
Initial release

[Unreleased]: https://github.com/offdev/csv/compare/1.2.0...master
[1.2.0]: https://github.com/offdev/csv/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/offdev/csv/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/offdev/csv/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/offdev/csv/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/offdev/csv/tree/1.0.0