<?php

namespace Jjbchunta\Async\Handlers;

use Exception;
use Jjbchunta\Async\Handlers\AsyncInterface;

class Async_Script implements AsyncInterface {
    protected $pipes;
    protected $process;
    protected $output;
    protected $command;
    protected $exit_code = null;

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

    public function result() {
        return $this->output;
    }

    public function __destruct() {
        $this->clean_up();
    }
}