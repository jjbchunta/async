<?php

namespace Jjbchunta\Async\Handlers;

use Exception;
use Jjbchunta\Async\Interfaces\AsyncHandlerInterface;
use Jjbchunta\Async\AsyncConfig;
use Jjbchunta\Async\AsyncException;

/**
 * The asynchronous handler responsible for initializing a child PHP process
 * running a specific file.
 */
class Async_Script implements AsyncHandlerInterface {
    /**
     * An indication of whether the current process this class has been created with
     * has issues with initializing. Not executing, but initializing. By default,
     * this is set to false.
     * @var bool
     */
    protected $uninitializable = false;
    /**
     * An indication of whether the current environment has the required functionality
     * at PHP's exposure to preform necessary operations in an asynchronous manner.
     * @var bool
     */
    protected $async_supported_env;
    /**
     * An instance of the `AsyncConfig` class detailing settings related to asynchronous
     * operations.
     * @var AsyncConfig
     */
    protected $config;
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

    public static function does_environment_support_async_functions() {
        return function_exists( 'proc_open' ) &&
                function_exists( 'proc_close' ) &&
                function_exists( 'proc_get_status' ) &&
                function_exists( 'stream_get_contents' ) &&
                function_exists( 'stream_set_blocking' );
    }

    public function __construct( $process, $config = null ) {
        $this->command = "php $process";

        // Check what we can even do
        $this->async_supported_env = self::does_environment_support_async_functions();

        // Initialize our config for the current process
        if ( empty( $config ) || !( $config instanceof AsyncConfig ) ) {
            $config = new AsyncConfig();
        }
        $this->config = $config;

        // `rerun` handles invocation, so just use that
        $this->rerun();
    }

    public function rerun() {
        if ( $this->uninitializable === true ) $this->throw_initialization_error();
        if ( $this->is_running() ) return;

        // Now, determine if we can preform this operation asynchronously
        if ( $this->async_supported_env ) {
            // echo "-- Async" . PHP_EOL;

            // We can proceed asynchronously!!!
            $isolated_streams = $this->config->get_isolated_std_stream_preferences();
            $isol_in_str = $isolated_streams[ 'in' ];
            $isol_out_str = $isolated_streams[ 'out' ];
            $isol_err_str = $isolated_streams[ 'err' ];
            $descriptor_spec = [
                ( !$isol_in_str ? fopen( 'php://stdin', 'r' ) : [ "pipe", "r" ] ), // in
                ( !$isol_out_str ? fopen( 'php://stdout', 'w' ) : [ "pipe", "w" ] ), // out
                ( !$isol_err_str ? fopen( 'php://stder', 'w' ) : [ "pipe", "w" ] ), // err
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
            if ( !is_resource( $this->process ) ) $this->throw_initialization_error();

            // Ensure this happens in the background and we're not blocked by this
            try {
                if ( $isol_out_str ) {
                    stream_set_blocking( $this->pipes[1], false );
                }
                if ( $isol_err_str ) {
                    stream_set_blocking( $this->pipes[2], false );
                }
            } catch( Exception $e ) {
                // The stream blocking could not be initialized
                // Just run synchronously as a result
            }
        } else {
            // echo "-- Sync" . PHP_EOL;
            // We cannot proceed asynchronously...

            // As such, we'll preform all of our operations right here
            $output = shell_exec( $this->command );
            if ( $output === false || $output === null ) {
                throw new Exception( "The process could not be initialized." );
            }

            // Allow any child classes to alter the value as desired
            $output = $this->invoke_output_sanitization_if_provided( $output );

            // And commit for all
            $this->output = $output;
        }
    }

    public function is_running() {
        if ( $this->uninitializable === true ) return false;
        if ( !$this->async_supported_env ) return false;
        if ( !is_resource( $this->process ) ) return false;

        $status = proc_get_status( $this->process );
        return $status[ 'running' ];
    }

    public function wait() {
        if ( $this->uninitializable === true ) return $this->output;
        if ( !$this->async_supported_env ) return $this->output;
        if ( !is_resource( $this->process ) ) return $this->output;

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
        $output = $this->invoke_output_sanitization_if_provided( $output );

        $this->output = $output;
        return $output;
    }

    public function stop( $force = true, $timeout = 5 ) {
        if ( $this->uninitializable === true ) return true;
        if ( !$this->async_supported_env ) return true;
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

        // Retrieve the output if one is provided before severing the connection
        $this->wait();
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
        if ( $this->uninitializable === true ) return;
        if ( !$this->async_supported_env ) return;
        if ( !is_resource( $this->process ) ) return;

        $stdin_pipe = $this->pipes[0];
        $stdout_pipe = $this->pipes[1];
        $stderr_pipe = $this->pipes[2];
        
        // Only close anonymous pipes
        $isolated_streams = $this->config->get_isolated_std_stream_preferences();
        if ( $isolated_streams[ 'in' ] ) fclose( $stdin_pipe );
        if ( $isolated_streams[ 'out' ] ) fclose( $stdout_pipe );
        if ( $isolated_streams[ 'err' ] ) fclose( $stderr_pipe );

        $this->exit_code = proc_close( $this->process );
        $this->process = null;
    }

    /**
     * @return int|null Retrieve the exit code as seen by a process termination
     * invocation.
     */
    public function get_exit_code() {
        return $this->exit_code;
    }

    /**
     * If the `sanitize_output` method has been defined in any extended instance of
     * this class, preform it's operation on the output of the command.
     * 
     * @param string $output The STDOUT stream from the process.
     * @throws \Exception If there was an error during sanitization, throw an exception.
     * @return mixed The sanitized output, if said function exists.
     */
    private function invoke_output_sanitization_if_provided( $output ) {
        if ( method_exists( $this, 'sanitize_output' ) ) {
            $output = $this->sanitize_output( $output );
            if ( is_subclass_of( $output, Exception::class ) ) {
                throw new Exception( "The output from the asynchrnous process could not be interpreted." );
            }
        }
        return $output;
    }

    /**
     * Throw an `AsyncException` exception.
     * 
     * @param string $message Optional. Include the message contained within the exception.
     * By default, this is set to: "The process could not be initialized."
     * @throws AsyncException
     */
    private function throw_initialization_error( $message = "The process could not be initialized." ) {
        $this->uninitializable = true;
        throw new AsyncException( $message );
    }
}