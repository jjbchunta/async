<?php

namespace Jjbchunta\Async;

use Exception;

/**
 * Pass a process that you wish to be handled asynchronously.
 * 
 * The current process types that are currently supported are:
 * - **URLs** - Preforming a request to a public web address. ex: `https://www.google.com`
 */
class Async {
    protected $process_type;
    protected $process_handler;

    private static $process_handlers = [
        'url' => '\\Jjbchunta\\Async\\Handlers\\Async_Curl'
    ];

    /**
     * Class constructor.
     * 
     * @param string $process The process we wish to asynchronously invoke.
     * @throws \Exception If a provided process could not be interpreted, or it's not
     * one of the specifically supported processes, throw an exception.
     */
    public function __construct( $process ) {
        // Determine the type of process we're working with
        $this->process_type = self::determine_process_type( $process );

        // Initiate the respective process
        $process_handler_class = self::retrieve_process_handler_class( $this->process_type );
        $this->process_handler = new $process_handler_class( $process );
    }

    /**
     * Determine the type of the process provided.
     * 
     * @param mixed $process The process we wish to check.
     * @throws \Exception If the provided process could not be determined, an exception
     * will be thrown.
     * @return string A string slug identifying the type of process.
     */
    public static function determine_process_type( $process ) {
        $chosen_process_type = null;

        // Loop through our handlers, attempting to determine if our process
        // matches the conditions set by said handlers
        foreach( self::$process_handlers as $type => $handler_class ) {
            if ( !method_exists( $handler_class, 'is_process_of_type' ) ) continue;
            if ( $handler_class::is_process_of_type( $process ) !== true ) continue;
            $chosen_process_type = $type;
            break;
        }

        if ( $chosen_process_type === null ) {
            throw new Exception( "The requested process cannot be handled asynchronously." );
        }
        return $chosen_process_type;
    }

    /**
     * Based on a process type, retrieve the handler class associoated with it.
     * 
     * @param string $type A string slug identifying the type of process.
     * @throws \Exception If the requested process type is not supported, or the handler
     * class associated with the process type is invalid, an exception will be thrown.
     */
    public static function retrieve_process_handler_class( $type ) {
        if ( !isset( self::$process_handlers[ $type ] ) ) {
            throw new Exception( "The provided process handler type is not supported." );
        }
        return self::$process_handlers[ $type ];
    }
}