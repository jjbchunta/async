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

    /**
     * Attempt to determine if the current PHP environment has all required functions
     * needed to preform the asynchronous operations of this handler.
     * 
     * @return bool True if yes, false if no.
     */
    public static function does_environment_support_async_functions();

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
     * @throws \Exception If a fatal error is thrown by the process while waiting,
     * it will be passed through this wait call.
     * @return mixed The value returned by the process on completion.
     */
    public function wait();

    /**
     * Forcefully terminate the current asynchronous operation if it's still running.
     * 
     * @param bool $force When true, forcefully kill the process with SIGKILL. When
     * false, provide the process a timeout to quit. If functions required to enable
     * graceful shutdowns are not present within the current PHP environment, this
     * flag will be ignored and set to forced.
     * @param int $timeout The timeout in seconds to provide a process time to quit.
     * Only relevant if `$force` is set to false. If the process does not close at
     * the end of the timeout window, it will be forcefully shutdown using SIGKILL.
     * If the provided timeout window is less than 0.1 (100ms), the call will be
     * ignored. By default, this value is set to 5.
     * @return bool True on success, false on failure.
     */
    public function stop( $force = true, $timeout = 5 );

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