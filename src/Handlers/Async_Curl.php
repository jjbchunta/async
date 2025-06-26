<?php

namespace Jjbchunta\Async\Handlers;

use Jjbchunta\Async\Handlers\Async_Script;

/**
 * The asynchronous handler responsible for outbound curl requests.
 */
class Async_Curl extends Async_Script {
    public static function is_process_of_type( $process ) {
        return true;
    }

    public function __construct( $process ) {
        /*
         * With this basically being a wrapper for the `Async_Script` class,
         * just construct the latter half of the command the handler will call
         * after the `php` keyword. Which is this case is an invocation of the
         * `Curl.php` script inside of the bin folder, which will handle all
         * the actual network requesting.
         */
        $executable = __DIR__ . "/../bin/Curl.php";
        $command = "$executable $process";
        parent::__construct( $command );
    }
}