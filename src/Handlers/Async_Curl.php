<?php

namespace Jjbchunta\Async\Handlers;

use Jjbchunta\Async\Handlers\Async_Script;

class Async_Curl extends Async_Script {
    public static function is_process_of_type( $process ) {
        return true;
    }

    public function __construct( $process ) {
        $executable = __DIR__ . "/../bin/Curl.php";
        $command = "$executable $process";
        parent::__construct( $command );
    }
}