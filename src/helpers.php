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