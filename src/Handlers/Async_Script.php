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
     * @var string
     */
    protected $output;
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
        return true;
    }

    public function __construct( $process ) {
        $this->command = "php $process";

        // We don't care about the pipes, so we define them but won't use them.
        $descriptor_spec = [
            0 => ["pipe", "r"], // in
            1 => ["pipe", "w"], // out
            2 => ["pipe", "w"] // err
        ];

        $this->pipes = [];
        $this->process = proc_open(
            $this->command,
            $descriptor_spec,
            $this->pipes
        );

        // Ensure the initialization was successful
        if ( !is_resource( $this->process ) ) {
            throw new Exception( "The process could not be initialized." );
        }

        stream_set_blocking( $this->pipes[1], false );
        stream_set_blocking( $this->pipes[2], false );
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
        }

        $this->clean_up();

        // Allow any child classes to alter the value as desired
        if ( method_exists( $this, 'sanitize_output' ) ) {
            $output = $this->sanitize_output( $output );
            if ( is_subclass_of( $output, Exception::class ) ) {
                // There was some error that occured during sanitization
            }
        }

        $this->output = $output;
        return $output;
    }

    public function get_exit_code() {
        return $this->exit_code;
    }

    public function stop() {
        if ( !$this->is_running() ) {
            return true; // Already stopped
        }

        proc_terminate( $this->process, 9 ); // 15 for more graceful nudge

        $this->clean_up();
        return true;
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

            $this->exitCode = proc_close( $this->process );
            $this->process = null;
        }
    }
}