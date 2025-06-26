# Asynchronous code for PHP.

A wrapper for various built-in non-blocking operations native to PHP.

This package can be installed via composer:

```
composer require jjbchunta/async
```

# Usage

``` php
use Jjbchunta\Async\Async;

$promise = new Async( $your_process );

// ...other stuff...

$output = await( $promise );
```

When it comes to this `Async` class, a _"process"_ that you pass as the main argument can be one of a few things:

- **PHP Files** - Execute a PHP script. ex: `".../path/to/file.php"`
- **URLs** - Preforming a request to a public web address. ex: `"https://www.google.com"`

> [!NOTE]
> The type of process passed into the `Async` class will be determined on the fly and will be handled accordingly.
> 
> As such, there is no need to put emphasis on the type of process you're looking to execute, be it through a
> special class or additional parameters. All of that is taken care of internally.