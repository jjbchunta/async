<?php

/*
 * This script will initialize a single request that will hit the homepage
 * of the website, check if a maintenance screen is present or not, and
 * then reinitialize the request to hit the homepage again and repreat the
 * process until this script is shutdown.
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
$max_request_duration = intval( $_SERVER[ 'argv' ][2] );
if ( $max_request_duration < 1 ) {
    echo "Invalid burst duration defined." . PHP_EOL;
    exit;
}

// Initialize composer and required functions
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize our bounds for watching out for a request for early close
$should_exit = false;
if ( function_exists( 'pcntl_async_signals' ) &&
        function_exists( 'pcntl_signal' ) )
{
    pcntl_async_signals( true );
    function signal_handler( $signo ) {
        global $report;
        echo serialize_curl_response_to_string( $report );
        exit;
    }
    pcntl_signal( SIGINT, 'signal_handler' );
    pcntl_signal( SIGTERM, 'signal_handler' );
    pcntl_signal( SIGHUP, 'signal_handler' );
}

/**
 * Analyze the response from a `parse_and_execute_curl_string_synchronously` call,
 * and determine if the requested webpage was under maintenance or not.
 * 
 * @param array $result Details about the cURL request.
 * @return bool True if (probably) yes, false if no.
 */
function is_site_under_maintenance( $result ) {
    $maintenance_text = 'Briefly unavailable for scheduled maintenance. Check back in a minute.';

    if ( !empty( $result[ 'error' ] ) ) {
        return true; // Error requesting webpage? Probably maintenance
    }

    if (
        is_string( $result[ 'body' ] ) &&
        strpos( $result[ 'body' ], $maintenance_text ) !== false
    ) {
        return true; // Maintenance screen detected
    }

    if ( isset( $result[ 'http_code' ] ) && $result[ 'http_code' ] === 200) {
        return false; // 200 code is typically good
    }

    return true;
}

// Initialize a request loop with a defined timeout
$start_time = microtime( true );
$report = [];

// Primary request loop
while (true) {
    // Preform a simple request, and check if we're under maintenance, adding it to the report
    $result = parse_and_execute_curl_string_synchronously( $url );
    $now = ( int ) ( hrtime( true ) / 1000 );
    $is_site_under_maintenance = is_site_under_maintenance( $result );
    // echo "[$now] " . ( $is_site_under_maintenance === false ? "Success" : "Failure" ) . PHP_EOL;
    $report[ $now ] = $is_site_under_maintenance ? 0 : 1;

    // Check if we have any reason to exit right now
    if ( $should_exit === true ) break; // If we've gotten a signal from the parent to quit
    $elapsed_time = microtime( true ) - $start_time;
    if ( $elapsed_time > $max_request_duration ) break; // If we've exceeded the maximum time this script should execute
}

// Upon the ending of the request loop, return the findings
echo serialize_curl_response_to_string( $report );
exit;