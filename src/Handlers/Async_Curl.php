<?php

namespace Jjbchunta\Async\Handlers;

use Jjbchunta\Async\Handlers\AsyncInterface;
use Jjbchunta\Async\Handlers\Async_Script;

class Async_Curl implements AsyncInterface {
    protected $handler;

    public static function is_process_of_type( $process ) {
        return true;
    }

    public function __construct( $process ) {
        $dir = rtrim( __DIR__, '/\\' ) . '/';
        $executable = "{$dir}../bin/Curl.php";
        $command = "$executable $process";
        $this->handler = new Async_Script( $command );
    }

    public function is_running() {
        return $this->handler->is_running();
    }

    public function wait() {
        return $this->handler->wait();
    }

    public function get_exit_code() {
        return $this->handler->get_exit_code();
    }

    public function stop() {
        return $this->handler->stop();
    }

    public function result() {
        return $this->handler->result();
    }
}