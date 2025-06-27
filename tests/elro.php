<?php

// "elro" being short for "example long running operation"

// Initialize our bounds for watching out for a request for early close
$should_exit = false;
if ( function_exists( 'pcntl_async_signals' ) &&
        function_exists( 'pcntl_signal' ) )
{
    pcntl_async_signals( true );
    function signal_handler($signo) {
        global $should_exit;
        echo "Caught signal, preparing to shut down..." . PHP_EOL;
        $should_exit = true;
    }
    pcntl_signal( SIGINT, 'signal_handler' );
    pcntl_signal( SIGTERM, 'signal_handler' );
    pcntl_signal( SIGHUP, 'signal_handler' );
}

// The "process"
for ( $i = 0; $i < 5; $i++ ) {
    sleep(1);
    if ( $should_exit === true ) {
        echo "Worker shutting down per caught signal for shutdown." . PHP_EOL;
        exit;
    }
}

echo "Worker completed without interuption." . PHP_EOL;
exit;