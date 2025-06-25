<?php

namespace Jjbchunta\Async\Handlers;

interface AsyncInterface {
    public static function is_process_of_type( $process );

    public function __construct( $process );
}