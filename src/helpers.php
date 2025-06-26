<?php

if ( !function_exists( 'await' ) ) {
    /**
     * Halt the current execution of the script for the asynchronous operation
     * to complete.
     * 
     * @return mixed The value returned by the process on completion.
     */
    function await( $promise ) {
        return $promise->wait();
    }
}

if ( !function_exists( 'parse_and_execute_curl_string' ) ) {
    /**
     * Parses a cURL command string and executes it using PHP's cURL extension.
     * 
     * This function assumes that the command is not prefixed with the `curl` keyword.
     *
     * @param string $command The cURL command string.
     * @return array An array containing the response body, HTTP code, and any errors.
     */
    function parse_and_execute_curl_string( $command ) {
        $args = str_getcsv($command, ' ', '"');

        $ch = curl_init();
        $headers = [];
        $post_data = null;

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_ENCODING, '' );

        // Map arguments to the associated setopt value
        for ( $i = 0; $i < count( $args ); $i++ ) {
            $arg = $args[$i];
            switch ( $arg ) {
                case '-X':
                case '--request':
                    // This flag expects a value
                    if ( isset( $args[++$i] ) ) {
                        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $args[$i] );
                    }
                    break;

                case '-H':
                case '--header':
                    // Accumulate headers into an array
                    if ( isset( $args[++$i] ) ) {
                        $headers[] = $args[$i];
                    }
                    break;

                case '-d':
                case '--data':
                case '--data-raw':
                    // Handle POST data
                    if ( isset( $args[++$i] ) ) {
                        $post_data = $args[$i];
                    }
                    break;

                case '-F':
                case '--form':
                    // Handle multipart/form-data
                    if ( isset( $args[++$i] ) ) {
                        if ( !is_array( $post_data ) ) {
                            // If we saw -d before, clear it and start a form array
                            $post_data = [];
                        }
                        $form_parts = explode( '=', $args[$i], 2 );
                        if ( count( $form_parts ) === 2 ) {
                            $post_data[ $form_parts[0] ] = $form_parts[1];
                        }
                    }
                    break;

                case '-u':
                case '--user':
                    // Handle basic authentication credentials
                    if ( isset( $args[++$i] ) ) {
                        curl_setopt( $ch, CURLOPT_USERPWD, $args[$i] );
                    }
                    break;

                case '-L':
                case '--location':
                    // Handle redirects
                    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                    break;

                case '-k':
                case '--insecure':
                    // Disable SSL certificate verification
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
                    break;

                case '--url':
                    // Explicitly set the URL
                    if ( isset( $args[++$i] ) ) {
                        curl_setopt( $ch, CURLOPT_URL, $args[$i] );
                    }
                    break;

                default:
                    // If it's not a flag, it's likely the URL
                    if ( preg_match( '/^https?:\/\//', $arg ) ) {
                        curl_setopt( $ch, CURLOPT_URL, $arg );
                    }
                    break;
            }
        }

        // Apply headers of any
        if ( !empty( $headers ) ) {
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        }

        // Apply POST data if any
        if ($post_data !== null) {
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
        }

        // Execute the request
        $response_body = curl_exec( $ch );
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error = curl_error( $ch );

        curl_close( $ch );

        return [
            'body' => $response_body,
            'http_code' => $http_code,
            'error' => $error,
        ];
    }
}