<?php

namespace Jjbchunta\Async;

use Exception;
use Throwable;

/**
 * An exception specific to instances where the asynchronous process could not be
 * initialized and ran, as opposed to an exception relating to something going
 * wrong within the process itself.
 */
class AsyncException extends Exception {
    public function __construct( $message, $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $code, $previous );
    }
}