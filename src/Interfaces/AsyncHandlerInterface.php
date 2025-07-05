<?php

namespace Jjbchunta\Async\Interfaces;

/**
 * General higher-level functions relevant to all asynchronous implementations,
 * alongside functions specific to the implementation of the unique handler.
 */
interface AsyncHandlerInterface extends AsyncInterface {
    /**
     * Attempt to determine if the current PHP environment has all required functions
     * needed to preform the asynchronous operations of this handler.
     * 
     * @return bool True if yes, false if no.
     */
    public static function does_environment_support_async_functions();
}