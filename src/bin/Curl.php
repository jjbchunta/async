<?php

// Ensure no unintended output is returned
ob_start();

// Extract the process from a command call
$process = count( $_SERVER[ 'argv' ] ) >= 2 ?
            implode( ' ', array_slice( $_SERVER[ 'argv' ], 1 ) ) :
            null;
if ( empty( $process ) ) {
    echo "A process must be defined." . PHP_EOL;
    exit;
}

// 
print_r( $process );