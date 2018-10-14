# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- ```ParserInterface``` now implements the ```Iterator``` interface, for easy iterations over a CSV stream
- New class ```Item``` which extends Laravel's ```Collection``` class, and adds an ```isValid``` method, which returns a validation result if the parser was given a validator

## [1.0.1] - 2018-10-14
### Removed
- Support for PHP 7.1

## [1.0.0] - 2018-10-14
Initial release

[Unreleased]: https://github.com/offdev/csv/compare/1.0.1...HEAD
[1.0.1]: https://github.com/offdev/csv/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/offdev/csv/tree/1.0.0