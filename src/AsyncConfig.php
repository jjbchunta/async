<?php

/**
 * A configuration class that can be passed into an `Async` instance for more
 * granular control over how the asynchronous process is handled.
 */
class AsyncConfig {
    private $use_isolated_stdin_streams = true;
    private $use_isolated_stdout_stream = true;
    private $use_isolated_stderr_stream = true;

    /**
     * Take a variable and ensure it's of type bool.
     * @param mixed $bool The (alleged) bool.
     * @return bool The sanitized bool on success, false if the value
     * passed was not a bool.
     */
    private static function filter_bool( $bool ) {
        if ( $bool === true || $bool === false ) return $bool;
        return filter_var(
            $bool,
            FILTER_VALIDATE_BOOLEAN,
            [ 'flags' => FILTER_NULL_ON_FAILURE ]
        ) ?? false;
    }

    /*
        Setters
    */

    /**
     * Globally define whether I/O streams should use their own isolated
     * streams.
     * 
     * To individually define the I/O stream settings, use each stream's
     * respective function:
     * 
     * * `use_isolated_stdin_stream`
     * * `use_isolated_stdout_stream`
     * * `use_isolated_stderr_stream`
     * 
     * By default, this is set to true. When false, the I/O stream of the
     * parent stream will be used instead.
     * 
     * @param bool $setting The desired state.
     */
    public function use_isolated_std_streams( $setting ) {
        $this->use_isolated_stdin_stream( $setting );
        $this->use_isolated_stdout_stream( $setting );
        $this->use_isolated_stderr_stream( $setting );
    }

    /**
     * Set whether the `stdin` stream should use it's own isolated I/O
     * stream.
     * 
     * To globally define this setting across all I/O streams, use
     * `use_isolated_std_streams`.
     * 
     * By default, this is set to true. When false, the I/O stream of the
     * parent stream will be used instead.
     * 
     * @param bool $setting The desired state.
     */
    public function use_isolated_stdin_stream( $setting ) {
        $this->use_isolated_stdin_stream = self::filter_bool( $setting );
    }

    /**
     * Set whether the `stdout` stream should use it's own isolated I/O
     * stream.
     * 
     * To globally define this setting across all I/O streams, use
     * `use_isolated_std_streams`.
     * 
     * By default, this is set to true. When false, the I/O stream of the
     * parent stream will be used instead.
     * 
     * @param bool $setting The desired state.
     */
    public function use_isolated_stdout_stream( $setting ) {
        $this->use_isolated_stdout_stream = self::filter_bool( $setting );
    }

    /**
     * Set whether the `stderr` stream should use it's own isolated I/O
     * stream.
     * 
     * To globally define this setting across all I/O streams, use
     * `use_isolated_std_streams`.
     * 
     * By default, this is set to true. When false, the I/O stream of the
     * parent stream will be used instead.
     * 
     * @param bool $setting The desired state.
     */
    public function use_isolated_stderr_stream( $setting ) {
        $this->use_isolated_stderr_stream = self::filter_bool( $setting );
    }

    /*
        Getters
    */

    /**
     * Return the desired isolated I/O stream preferences.
     * 
     * @return array{err: mixed, in: mixed, out: mixed} A keyed associative
     * array detailing the preferences. True where an isolated stream is
     * desired, and false when not. An example of this array returned would
     * look like:
     * 
     * ```
     * [
     *     'in' => (Bool),
     *     'out' => (Bool),
     *     'err' => (Bool)
     * ]
     * ```
     */
    public function get_isolated_std_stream_preferences() {
        return [
            'in' => $this->use_isolated_stdin_streams,
            'out' => $this->use_isolated_stdin_streams,
            'err' => $this->use_isolated_stdin_streams
        ];
    }
}