# Asynchronous code for PHP.

A wrapper for various built-in non-blocking operations native to PHP.

This package can be installed via composer:

```
composer require jjbchunta/async
```

# Usage

### URL Requests

``` php
use Jjbchunta\Async\Async;

$promise = new Async( "https://www.google.com" );

// ...other stuff...

await( $promise );
```