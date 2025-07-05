# Changelog

All notable changes to `async` will be documented in this file.

## v1.2.1 - 7/5/25

* Errors specific to the initialization of an async process rather than the async process itself are handled using `AsyncException`
* Depreciating the static function `is_process_of_type` in the interface
    * The type evaluation is internal to the `Async` class now as opposed to individually defined by respective process handlers
* Small internal file and inheritance changes
* Omitting `composer.lock` file

## v1.2.0 - 6/28/25

* Underlying async functionality of a process can be altered using the `AsyncConfig` class
* Small code refactoring
* Suppressed PHP warnings

## v1.1.2 - 6/27/25

* A safely stopped process will still have it's result available

## v1.1.1 - 6/27/25

* Omitted some accidental front-facing debugging
* Suppressed PHP warnings and errors

## v1.1.0 - 6/27/25

* Synchronous fallback when required functionality is missing
* Fatal errors are returned in the output
* Processes can be gracefully shutdown as opposed to only killed

## v1.0.1 - 6/26/25

* Warning supressions
* Small code refactoring

## v1.0.0 - 6/26/25

* First stable release
