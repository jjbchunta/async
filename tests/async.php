<?php

// Extract the process from a command call
$process = count( $_SERVER[ 'argv' ] ) >= 2 ?
            array_slice( $_SERVER[ 'argv' ], 1, 1 )[0] :
            null;
if ( empty( $process ) ) {
    echo "A process must be defined." . PHP_EOL;
    exit;
}

// Simply invoke and let the library handle the rest
require __DIR__ . '/../vendor/autoload.php';
use Jjbchunta\Async\Async;

echo "Invoking async process..." . PHP_EOL;
$promise = new Async( $process );
echo "Async process invoked!" . PHP_EOL;

echo "Example operations..." . PHP_EOL;
sleep(2);
echo "Example process completed." . PHP_EOL;

echo "Waiting process finish..." . PHP_EOL;
$result = await( $promise );
print_r( $result );
echo "Process await finished!" . PHP_EOL;