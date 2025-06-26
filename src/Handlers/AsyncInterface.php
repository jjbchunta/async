<?php

namespace Jjbchunta\Async\Handlers;

interface AsyncInterface {
    /**
     * Attempt to determine if the included process would be supported by this handler.
     * 
     * @param mixed $process The process we wish to check.
     * @return bool True if yes, false if no.
     */
    public static function is_process_of_type( $process );

    public function __construct( $process );

    /**
     * Check if the current asynchronous operation is still running.
     * 
     * @return bool True if yes, false if no.
     */
    public function is_running();

    /**
     * Halt the current execution of the script for the asynchronous operation
     * to complete.
     * 
     * @return mixed The value returned by the process on completion.
     */
    public function wait();

    /**
     * @return mixed Retrieve the output returned not by the process, but by the action of
     * closing the operation.
     */
    public function get_exit_code();

    /**
     * Forcefully terminate the current asynchronous operation if it's still running.
     * 
     * @param int $timeout Time in seconds to wait for graceful shutdown.
     * @param bool $force Forcefully kill with SIGKILL if timeout is reached.
     * @return bool True on success, false on failure.
     */
    public function stop();

    /**
     * @return mixed Retrieve the value returned by the process on completion.
     * 
     * This value is also returned after a `wait` call.
     */
    public function result();
}