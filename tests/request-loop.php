<?php

/*
 * This script will initialize a specified number of looping request scripts
 * (of which exists at this location: `bin/looping-request.php`) to hit the
 * homepage of the website, checking for maintenance messages, and amalgamating
 * the resulting data.
 */

// Ensure we have all required command line arguments
if ( intval( $_SERVER[ 'argc' ] ) < 3 ) {
    echo "Required parameters missing." . PHP_EOL;
    exit;
}
// Retrieve the site URL we should hit
$url = $_SERVER[ 'argv' ][1];
if ( empty( $url ) ) {
    echo "Invalid URL defined." . PHP_EOL;
    exit;
}
// Retrieve the number of requests we wish to concurrently hit the server with
$concurrent_requests = intval( $_SERVER[ 'argv' ][2] );
if ( $concurrent_requests < 1 ) {
    echo "Invalid number of concurrent requests defined." . PHP_EOL;
    exit;
}

// Initialize composer
require_once __DIR__ . '/../vendor/autoload.php';
use Jjbchunta\Async\Async;

// Initialize our bounds for watching out for a request for early close
if ( function_exists( 'pcntl_async_signals' ) &&
        function_exists( 'pcntl_signal' ) )
{
    pcntl_async_signals( true );
    function signal_handler( $signo ) {
        echo "Termination request recieved..." . PHP_EOL;

        // Loop through all of the request pools we started, and just stop them all
        // Cancel 'em all
        global $request_pool;
        $all_reports = [];
        foreach ( $request_pool as $request ) {
            $request->stop( false );
            try {
                // Amalgamate all of the uptime reports
                $report = $request->result();
                echo $report . PHP_EOL;
                $report = deserialize_curl_response_from_string( $report );
                if ( $report ) {
                    $all_reports[] = $report;
                }
            } catch ( Exception $e ) {
                continue;
            }
        }

        // Compress them into one big list and pass back
        $final_report = [];
        foreach ( $all_reports as $report ) {
            $final_report += $report;
        }
        ksort( $final_report ); // In order
        print_r( $final_report );
        echo serialize_curl_response_to_string( $final_report );
        exit;
    }
    pcntl_signal( SIGINT, 'signal_handler' );
    pcntl_signal( SIGTERM, 'signal_handler' );
    pcntl_signal( SIGHUP, 'signal_handler' );
}

// Initialize the looping request pool according to the desired number of
// concurrent requests
$looping_request_source = __DIR__ . '/looping-request.php';
$command = "\"$looping_request_source\" $url";
$request_pool = [];
for ( $reqid = 0; $reqid < $concurrent_requests; $reqid++ ) {
    $request_pool[] = new Async( $command );
}
while(true) {
    usleep( 250000 ); // ~250ms
}