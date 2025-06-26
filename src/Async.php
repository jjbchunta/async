<?php

namespace Jjbchunta\Async;

use Exception;
use Jjbchunta\Async\Handlers\AsyncInterface;

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
     * 
     * When passing processes into the `Async` class, said process will step through
     * the respective `is_process_of_type` implementations of each class in order.
     * @var array
     */
    private static $process_handlers = [
        'url' => '\\Jjbchunta\\Async\\Handlers\\Async_Curl',
        'script' => '\\Jjbchunta\\Async\\Handlers\\Async_Script'
    ];

    /**
     * Class constructor.
     * 
     * @param string $process The process we wish to asynchronously invoke.
     * @throws \Exception If a provided process could not be interpreted, it's not one
     * of the specifically supported processes, or the process could not be initialized
     * asynchronously according to the process handler, an exception will be thrown.
     */
    public function __construct( $process ) {
        // Determine the type of process we're working with
        $this->process_type = self::is_process_of_type( $process );

        // Initiate the respective process
        $process_handler_class = self::retrieve_process_handler_class( $this->process_type );
        $this->process_handler = new $process_handler_class( $process );
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
     * @throws \Exception If the provided process could not be determined, an exception
     * will be thrown.
     * @return string A string slug identifying the type of process.
     */
    public static function is_process_of_type( $process ) {
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

    public function stop() {
        return $this->process_handler->stop();
    }

    public function result() {
        return $this->process_handler->result();
    }

    public function rerun() {
        $this->process_handler->rerun();
    }
}