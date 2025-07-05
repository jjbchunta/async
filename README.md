# Asynchronous code for PHP.

![Version](https://img.shields.io/badge/Version-1.2.1-brightgreen)

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

- **cURL Commands** - Preforming a web request using CURL. ex: `"https://api.domain.com -X POST ..."`
- **PHP Files** - Execute a PHP script. ex: `".../path/to/file.php"`

> [!NOTE]
> The type of process passed into the `Async` class will be determined on the fly and will be handled accordingly.

### Error handling

If something goes wrong while attempting to initialize the process, rather than something wrong with the process itself, those exceptions are handled by the `AsyncException` class.

``` php
use Jjbchunta\Async\Async;
use Jjbchunta\Async\AsyncException;

try {
    $promise = new Async( $your_process );
} catch ( AsyncException $e ) {
    // ...something went wrong initializing the process...
}

// ...other stuff...

try {
    $output = await( $promise );
} catch ( Exception $e ) {
    // ...this is an error directly from the process...
}
```

Any other exception relating to the actual execution of the code being run will be thrown as it's respective type when attempting to call the `->wait();` or `->result();` functions.

## Also at your disposal...

Additional function calls available for any `Async` instance.

* `->is_running();` - True / false whether the process is still running.
* `->wait();` - Block the current script from proceeding until the process has finished, returning the result on completion.
* `->stop();` - Forcefully terminate the process.
* `->result();` - Retrieve the result from the last invocation.
* `->rerun();` - Restart the exact same process again.

## Sychronous fallback

Different processes require different functionality, and sometimes that functionality is not at the dispoal of the current PHP environment. If that's the case where one of these operations are requested for an asynchronous execution, but the necessary functionality is missing, the main process will be blocking. However, whether sync or async, all code will still work the same regardless. The process will still run and the output will still be the same, and any supporting functions like `->result();` and `->rerun();` will still do what they do and return what they return, just synchronously.

If you're having doubts regarding whether a process can run asynchronously in the current environment, or you'd just like to be sure, you can make a static call to the `Async` class as seen below:

``` php
Async::can_process_run_async( $your_process );
```

## Under the hood

The biggest desire of mine when it comes to this project is for everything to be completely native to PHP with the widest support umbrella. That being the best I can do for a plug-and-play solution to the problem of asynchronous operations inside of a, normally, synchronous language.

As such, this process of achieving "asynchronous" behavior behind the curtain typically comes down to initializing another PHP process where your code or request can run isolated from the main script. Furthermore, some standard I/O pipes will be left open for communication between this parent and child PHP processes for state and output. As for this implementation, it's done using functions like `proc_open`.

## Changelog

Please see [CHANGELOG](https://github.com/jjbchunta/async/blob/main/CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](https://github.com/jjbchunta/async/blob/main/LICENSE) for more information.
