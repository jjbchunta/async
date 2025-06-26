<?php

namespace Jjbchunta\Async\Handlers;

interface AsyncInterface {
    public static function is_process_of_type( $process );

    public function __construct( $process );

    public function is_running();

    public function wait();

    public function get_exit_code();

    public function close();
}