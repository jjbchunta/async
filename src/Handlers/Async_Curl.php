<?php

namespace Jjbchunta\Async\Handlers;

use Exception;
use Jjbchunta\Async\Handlers\Async_Script;

/**
 * The asynchronous handler responsible for outbound curl requests.
 */
class Async_Curl extends Async_Script {
    public static function is_process_of_type( $process ) {
        if ( !is_string( $process ) ) return false;

        // Evaluate if there is a prefix of an http or https
        return preg_match( '/^https?:\/\//', $process ) == 1;
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

    /**
     * Take the full STDOUT from a successfully closed stream, and sanitize
     * it into some form of more usable data type.
     * 
     * @param string $output The STDOUT stream.
     * @throws \Exception If the stream cannot be properly sanitized, an
     * exception will be thrown.
     * @return mixed The sanitized stream in the desired data type.
     */
    protected function sanitize_output( $output ) {
        $output_array = deserialize_curl_response_from_string( $output );
        if ( $output_array === null ) {
            throw new Exception( "Unable to interpret HTTP response." );
        }
        return $output_array;
    }
}