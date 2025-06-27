<?php

namespace Jjbchunta\Async\Handlers;

use Exception;
use Jjbchunta\Async\Handlers\AsyncInterface;

/**
 * The asynchronous handler responsible for initializing a child PHP process
 * running a specific file.
 */
class Async_Script implements AsyncInterface {
    /**
     * An indication of whether the current environment has the required functionality
     * at PHP's exposure to preform necessary operations.
     * @var bool
     */
    protected $async_supported_env;
    /**
     * The STD in, out, and error pipes tied to the child process.
     * @var array
     */
    protected $pipes;
    /**
     * The handle tied to the child process.
     * @var resource
     */
    protected $process;
    /**
     * The full output stream of a successful asynchronous invocation.
     * 
     * If extended by a child process and said child process defined a `sanitize_output`
     * function discoverable by `Async_Script`, that function will be passed this output
     * stream with the ability to sanitize it before being commited to this variable.
     * 
     * @var string|mixed
     */
    protected $output = null;
    /**
     * The original command that began the child process.
     * @var string
     */
    protected $command;
    /**
     * The output returned not by the process, but by the action of closing the operation.
     * @var int|null
     */
    protected $exit_code = null;

    /*
        Interface Required
    */

    public static function is_process_of_type( $process ) {
        if ( !is_string( $process ) ) return false;

        // Evaluate if there is a pointer to a ".php" file
        return preg_match( '/\.php/', $process ) == 1;
    }

    public static function does_environment_support_async_functions() {
        return function_exists( 'proc_open' ) &&
                function_exists( 'proc_close' ) &&
                function_exists( 'proc_get_status' ) &&
                function_exists( 'stream_get_contents' ) &&
                function_exists( 'stream_set_blocking' );
    }

    public function __construct( $process ) {
        $this->command = "php $process";

        // Check what we can even do
        $this->async_supported_env = self::does_environment_support_async_functions();

        // `rerun` handles invocation, so just use that
        $this->rerun();
    }

    public function rerun() {
        if ( $this->is_running() ) return;

        // Now, determine if we can preform this operation asynchronously
        if ( $this->async_supported_env ) {
            // We can proceed asynchronously!!!
            $descriptor_spec = [
                0 => ["pipe", "r"], // in
                1 => ["pipe", "w"], // out
                2 => ["pipe", "w"] // err
            ];

            // Ensure no conflicts
            $this->output = null;
            $this->exit_code = null;
            $this->pipes = [];

            // Attempt to open a seperate PHP process
            $this->process = proc_open(
                $this->command,
                $descriptor_spec,
                $this->pipes
            );
            if ( !is_resource( $this->process ) ) {
                throw new Exception( "The process could not be initialized." );
            }

            // Ensure this happens in the background and we're not blocked by this
            stream_set_blocking( $this->pipes[1], false );
            stream_set_blocking( $this->pipes[2], false );
        } else {
            // We cannot proceed asynchrnously...
        }
    }

    public function is_running() {
        if ( !is_resource( $this->process ) ) {
            return false;
        }
        $status = proc_get_status( $this->process );
        return $status[ 'running' ];
    }

    public function wait() {
        if ( !is_resource( $this->process ) ) {
            return '';
        }

        // Re-enable blocking to wait for all content
        stream_set_blocking( $this->pipes[1], true );
        stream_set_blocking( $this->pipes[2], true );

        $output = stream_get_contents( $this->pipes[1] );
        $error_output = stream_get_contents( $this->pipes[2] );

        if ( !empty( $error_output ) ) {
            // There was some error that occured during execution
            throw new Exception( "Error occured attempting to run asynchronous process: " . $error_output );
        }

        $this->clean_up();

        // Allow any child classes to alter the value as desired
        if ( method_exists( $this, 'sanitize_output' ) ) {
            $output = $this->sanitize_output( $output );
            if ( is_subclass_of( $output, Exception::class ) ) {
                // There was some error that occured during sanitization
                throw new Exception( "The output from the asynchrnous process could not be interpreted." );
            }
        }

        $this->output = $output;
        return $output;
    }

    public function stop( $force = true, $timeout = 5 ) {
        if ( !$this->is_running() ) {
            return true; // Already stopped
        }

        $successfully_terminated = false;

        if (
            $force === false && $timeout >= 0.1 && // We're asking for a graceful shutdown with a valid timeout window
            function_exists( 'pcntl_async_signals' ) && // A function required to allow for a graceful shutdown
            function_exists( 'pcntl_signal' ) // A function required to allow for a graceful shutdown
        ) {
            // Attempt to gracefully shut down the process in accordance with the timeout
            proc_terminate( $this->process, 15 );

            $start_time = microtime( true );
            while (true) {
                // If the process is no longer running, it shut down successfully
                if ( !$this->is_running() ) {
                    break;
                }

                // Check if the timeout has been exceeded
                $elapsed_time = microtime( true ) - $start_time;
                if ( $elapsed_time > $timeout ) {
                    // We've taken too long, kill it
                    proc_terminate( $this->process, 9 );
                    $successfully_terminated = true;
                    break;
                }

                // LOOP
                usleep( 100000 ); // ~100ms
            }
        } else {
            // Otherwise, just put it out of it's misery
            proc_terminate( $this->process, 9 );
            $successfully_terminated = true;
        }

        $this->clean_up();
        return $successfully_terminated;
    }

    public function result() {
        return $this->output;
    }

    public function __destruct() {
        $this->clean_up();
    }

    /*
        Additional Functions
    */

    /**
     * Assuming that there is still an active connection to the asynchronous
     * operation, ensure all ties and pipes are properly and safely closed.
     * 
     * @return void
     */
    protected function clean_up() {
        if ( is_resource( $this->process ) ) {
            $stdin_pipe = $this->pipes[0];
            $stdout_pipe = $this->pipes[1];
            $stderr_pipe = $this->pipes[2];
            
            fclose( $stdin_pipe );
            fclose( $stdout_pipe );
            fclose( $stderr_pipe );

            $this->exit_code = proc_close( $this->process );
            $this->process = null;
        }
    }

    /**
     * @return int|null Retrieve the exit code as seen by a process termination
     * invocation.
     */
    public function get_exit_code() {
        return $this->exit_code;
    }
}