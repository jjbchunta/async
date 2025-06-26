# Asynchronous code for PHP.

A wrapper for various built-in non-blocking operations native to PHP, a language notorious for being single-threaded and very much blocking.

This package can be installed via composer:

```
composer require jjbchunta/async
```

## Usage

``` php
use Jjbchunta\Async\Async;

$promise = new Async( $your_process );

// ...other stuff...

$output = await( $promise );
```

When it comes to this `Async` class, a _"process"_ that you pass as the main argument can be one of a few things:

- **URLs** - Preforming a web request using CURL. ex: `"https://api.domain.com -X POST ..."`
- **PHP Files** - Execute a PHP script. ex: `".../path/to/file.php"`

> [!NOTE]
> The type of process passed into the `Async` class will be determined on the fly and will be handled accordingly.
> 
> As such, there is no need to put emphasis on the type of process you're looking to execute, be it through a
> special class or additional parameters. All of that is taken care of internally.

## Also at your disposal...

Additional function calls available for any `Async` instance.

* `->is_running();` - True / false whether the process is still running.
* `->wait();` - Block the current script from proceeding until the process has finished, returning the result on completion.
* `->stop();` - Forcefully terminate the process.
* `->result();` - Retrieve the result from the last invocation.
* `->rerun();` - Restart the exact same process again.

## Under the hood

The biggest desire of mine when it comes to this project is for everything to be completely native to PHP with the widest support umbrella. That being the best I can do for a plug-and-play solution to the problem of asynchronous operations inside of a, normally, synchronous language.

As such, this process of achieving "asynchronous" behavior behind the curtain typically comes down to initializing another PHP process where your code or request can run isolated from the main script. Furthermore, some standard I/O pipes will be left open for communication between this parent and child PHP processes for state and output. As for this implementation, it's done using functions like `proc_open`.

## Changelog

Please see [CHANGELOG](https://github.com/jjbchunta/async/blob/main/CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](https://github.com/jjbchunta/async/blob/main/LICENSE) for more information.