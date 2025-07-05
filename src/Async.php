<?php

namespace Jjbchunta\Async;

use Exception;
use Jjbchunta\Async\Interfaces\AsyncInterface;

/**
 * Pass a process that you wish to be handled asynchronously.
 * 
 * The current process types that are currently supported are:
 * - **URLs** - Preforming a web request using CURL. ex: `"https://api.domain.com -X POST ..."`
 * - **PHP Files** - Execute a PHP script. ex: `.../path/to/file.php`
 */
class Async implements AsyncInterface {
    /**
     * A string slug identifying the type of process.
     * @var string
     */
    protected $process_type;
    /**
     * An instance of the handler class for the respective process type.
     * @var object
     */
    protected $process_handler;

    /**
     * The association between process types and handler classes, that being the key
     * to value pair relationship.
     * @var array
     */
    private static $process_handlers = [
        'url' => '\\Jjbchunta\\Async\\Handlers\\Async_Curl',
        'script' => '\\Jjbchunta\\Async\\Handlers\\Async_Script'
    ];

    /**
     * A static callback that will determine if a process can be run asynchronously
     * in the current PHP environment.
     * 
     * @param mixed $process The process we wish to asynchronously invoke.
     * @return bool True if yes, false if no.
     */
    public static function can_process_run_async( $process ) {
        try {
            $process_type = self::is_process_of_type( $process );
            $process_handler_class = self::retrieve_process_handler_class( $process_type );
            return $process_handler_class::does_environment_support_async_functions() === true;
        } catch ( Exception $e ) {
            return false;
        }
    }

    public function __construct( $process, $config = null ) {
        // Determine the type of process we're working with
        $this->process_type = self::is_process_of_type( $process );

        // Initiate the respective process
        $process_handler_class = self::retrieve_process_handler_class( $this->process_type );
        $this->process_handler = new $process_handler_class( $process, $config );
    }

    /**
     * Attempt to determine if the included process would be supported by this handler.
     * 
     * However, as implemented by the `Async` class, instead of checking if this process
     * is supported by a specific handler, this will instead take a look at all provided
     * handler classes and determine which one would be able to best process the
     * provided process, and return that class name.
     * 
     * @param mixed $process The process we wish to check.
     * @throws Exception If the provided process could not be determined, an exception
     * will be thrown.
     * @return string A string slug identifying the type of process.
     */
    public static function is_process_of_type( $process ) {
        $chosen_process_type = null;
        $cannot_handle_process_error = "The requested process cannot be handled asynchronously.";
        
        // Type check
        if ( empty( $process ) ) throw new Exception( $cannot_handle_process_error );

        // Is PHP file?
        if ( preg_match( '/\.php/', $process ) == 1 ) return 'script';

        // Is URL?
        if ( preg_match( '/^https?:\/\//', $process ) == 1 ) return 'url';

        if ( $chosen_process_type === null ) throw new Exception( $cannot_handle_process_error );
        return $chosen_process_type;
    }

    /**
     * Based on a process type, retrieve the handler class associoated with it.
     * 
     * @param string $type A string slug identifying the type of process.
     * @throws Exception If the requested process type is not supported, or the handler
     * class associated with the process type is invalid, an exception will be thrown.
     */
    public static function retrieve_process_handler_class( $type ) {
        if ( !isset( self::$process_handlers[ $type ] ) ) {
            throw new Exception( "The provided process handler type is not supported." );
        }
        return self::$process_handlers[ $type ];
    }

    /**
     * @return string Retrieve the evaluated process type by said provided process.
     */
    public function type() {
        return $this->process_type;
    }

    /* Interface Asks */

    public function is_running() {
        return $this->process_handler->is_running();
    }

    public function wait() {
        return $this->process_handler->wait();
    }

    public function stop( $force = true, $timeout = 5 ) {
        return $this->process_handler->stop( $force, $timeout );
    }

    public function result() {
        return $this->process_handler->result();
    }

    public function rerun() {
        $this->process_handler->rerun();
    }
}