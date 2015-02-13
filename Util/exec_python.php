<?php
/*****************************************************************************

Provides Utilities for Executing Python Code
============================================

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/**
 * Executes and returns the results of a few lines of Python code.
 *
 */
function exec_python( $lines ) {

    //set Python binary here
    $python = '/usr/local/bin/python';

    //imports and set up a reporting array for output
    $code = "import json\nimport sys\ndata = []\n";

    //construct the requested Python code
    $code .= implode( "\n", $lines ) . "\n";

    //write the data (that was populated by `$lines`) as JSON to stdout
    $code .= "sys.stdout.write( json.dumps( data ) )\n";

    //wrap in single-quotes, and escape single-quotes and back-slashes
    $code = escapeshellarg( $code );

    //construct a command to run Python from external execution
    $command = $python . " -c $code 2>&1";

    //create an array to capture the command output, and a shell return
    $output = [];
    $status = 0;

    //execute Python, and pass the code as a literal
    exec( $command, $output, $status );

    //ensure Python finished successfully
    if( $status != 0 ) {
        return false;
    }

    //decode all output as JSON
    $data = json_decode( implode( "\n", $output ) );

    //return the structured data
    return $data;
}

/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    $data = exec_python( [
        'data.append( "line one" )',
        'data.append( 42 )',
        'data.append( 3.14159 )',
        'data.append( range( 3, 11, 2 ) )',
        "data.append( 'last \\ line' )"
    ] );

    if( $data === false ) {
        echo "Python returned failure.";
    }

    print_r( $data );

}

