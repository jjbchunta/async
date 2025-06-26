<?php

namespace Jjbchunta\Async\Handlers;

/**
 * The outline of expected public functions that an asynchronous handler should have.
 */
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

    /**
     * Take the provided process, and run it one more time.
     * 
     * This will only become available after an initial invocation, and if the
     * current process is not running.
     * 
     * It should also be noted that any saved values from the result of the previous
     * invocation will be flushed upon calling this value, but retrieval of the new
     * value on completion will still be available through the same means of using
     * `wait` and `result` calls.
     * 
     * @throws \Exception If the process is unable to be properly re-initialized, an
     * exception will be thrown.
     * @return void
     */
    public function rerun();
}