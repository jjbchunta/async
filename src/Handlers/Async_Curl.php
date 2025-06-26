<?php

namespace Jjbchunta\Async\Handlers;

use Jjbchunta\Async\Handlers\AsyncInterface;
use Jjbchunta\Async\Handlers\Async_Script;

class Async_Curl implements AsyncInterface {
    public static function is_process_of_type( $process ) {
        return true;
    }

    public function __construct( $process ) {
    }

    public function is_running() {

    }

    public function wait() {
        
    }

    public function get_exit_code() {

    }

    public function close() {
        
    }
}