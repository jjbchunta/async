<?php

namespace Jjbchunta\Async\Handlers;

use Jjbchunta\Async\Handlers\AsyncInterface;

class Async_Curl implements AsyncInterface {
    public static function is_process_of_type( $process ) {
        return true;
    }

    public function __construct( $process ) {
        // Make the web request
    }
}