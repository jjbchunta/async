<?php

// Ensure no unintended output is returned
ob_start();

// Extract the process from a command call
$curl_cmd = intval( $_SERVER[ 'argc' ] ) >= 2 ?
            implode( ' ', array_slice( $_SERVER[ 'argv' ], 1 ) ) :
            null;
if ( empty( $curl_cmd ) ) {
    echo "A process must be defined." . PHP_EOL;
    exit;
}

// Execute the command, returning the output
if ( !function_exists( 'parse_and_execute_curl_string_synchronously' ) ) {
    require_once __DIR__ . '/../helpers.php';
}
$result = parse_and_execute_curl_string_synchronously( $curl_cmd );

// Clean buffer and return result
ob_end_clean();
$compressed_result = serialize_curl_response_to_string( $result );
echo $compressed_result;