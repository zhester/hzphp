<?php
/*****************************************************************************

Miscellaneous Value Testing Functions
=====================================

description

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

/**
 * Namespace container for static testing functions.
 */
class Test {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Tests a value for emptiness.  This test replaces PHP's built-in
     * `empty()` function since it doesn't need to be backwards-compatible
     * with old, broken scripts.
     *
     * The major improvement is empty string testing.  This considers the
     * strings "0" and "0.0" to NOT be empty.
     *
     * @param value The value to test for emptiness
     * @return      True if the value is considered empty
     */
    public static function isEmpty( &$value ) {
        if( isset( $value ) == false ) {
            return true;
        }
        if( is_string( $value ) ) {
            return strlen( $value ) == 0;
        }
        if( is_array( $value ) ) {
            return count( $value ) == 0;
        }
        return empty( $value );
    }


    /**
     * Tests any PHP array to determine if it's a numerically-indexed array.
     *
     * @param array The array to test
     * @param flags Behavior/performance flags:
     *                  1: thoroughly test regardless of length
     * @return      True if the array is numerically indexed
     */
    public static function isSequential( $array, $flags = 0 ) {

        //see if we should test everything
        if( ( ( $flags & 1 ) == 1 ) || ( count( $array ) < 42 ) ) {

            //see if any of the keys are strings
            $num_string_keys = count(
                array_filter( array_keys( $array ), 'is_string' )
            );

            //sequential arrays should have no string keys
            return $num_string_keys == 0;
        }

        //efficient test to cover the most common sequential arrays
        return isset( $array[ 0 ] );
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    /*======================================================================*/

    echo "isEmpty()\n";

    $cases = [
        [ 'null', true ],
        [ 'false', true ],
        [ 'true', false ],
        [ '""', true ],
        [ '"0"', false ],
        [ '"0.0"', false ],
        [ '"\0"', false ],
        [ '" "', false ],
        [ '"\n"', false ],
        [ '0', true ],
        [ '1', false ],
        [ '0.0', true ],
        [ '1.0', false ],
        [ '[]', true ],
        [ '[ 42 ]', false ],
        //[ 'stdClass 0', false ],
        //[ 'stdClass 1', false ],
        [ '(before) $test', true ],
        [ '(after) $test', false ],
        [ '(unset) $test', true ]
    ];

    $case_labels = array_column( $cases, 0 );
    $pad = max( array_map( 'strlen', $case_labels ) );

    foreach( $cases as $case ) {
        switch( $case[ 0 ] ) {
            case 'null':
                $value = null;
                $result = Test::isEmpty( $value );
                break;
            case 'false':
                $value = false;
                $result = Test::isEmpty( $value );
                break;
            case 'true':
                $value = true;
                $result = Test::isEmpty( $value );
                break;
            case '""':
                $value = "";
                $result = Test::isEmpty( $value );
                break;
            case '"0"':
                $value = "0";
                $result = Test::isEmpty( $value );
                break;
            case '"0.0"':
                $value = "0.0";
                $result = Test::isEmpty( $value );
                break;
            case '"\0"':
                $value = "\0";
                $result = Test::isEmpty( $value );
                break;
            case '" "':
                $value = " ";
                $result = Test::isEmpty( $value );
                break;
            case '"\n"':
                $value = "\n";
                $result = Test::isEmpty( $value );
                break;
            case '0':
                $value = 0;
                $result = Test::isEmpty( $value );
                break;
            case '1':
                $value = 1;
                $result = Test::isEmpty( $value );
                break;
            case '0.0':
                $value = 0.0;
                $result = Test::isEmpty( $value );
                break;
            case '1.0':
                $value = 1.0;
                $result = Test::isEmpty( $value );
                break;
            case '[]':
                $value = [];
                $result = Test::isEmpty( $value );
                break;
            case '[ 42 ]':
                $value = [ 42 ];
                $result = Test::isEmpty( $value );
                break;
            case 'stdClass 0':
                $value = new \stdClass();
                $result = Test::isEmpty( $value );
                break;
            case 'stdClass 1':
                $value = new \stdClass();
                $value->prop = true;
                $result = Test::isEmpty( $value );
                break;
            case '(before) $test':
                $result = Test::isEmpty( $test );
                break;
            case '(after) $test':
                $test = true;
                $result = Test::isEmpty( $test );
                break;
            case '(unset) $test':
                unset( $test );
                $result = Test::isEmpty( $test );
                break;
            default:
                break;
        }
        echo '| ', str_pad( $case[ 0 ], $pad, ' ', STR_PAD_LEFT );
        echo $result ? ' | true ' : ' | false';
        if( $result != $case[ 1 ] ) {
            echo ' | FAILED |';
        }
        else {
            echo ' | PASSED |';
        }
        echo "\n";
    }

    echo "\n";

    /*======================================================================*/

    echo "isSequential()\n";

    $cases = [
        [ [ 0, 1, 2, 3 ], true ],
        [ [ 1, 2, 3 ], true ],
        [ [ 'a' => 1, 'b' => 2 ], false ],
        [ [ 0, 'b' => 2 ], false ]
    ];
    $case_labels = array_map( 'json_encode', array_column( $cases, 0 ) );
    $pad = max( array_map( 'strlen', $case_labels ) );
    foreach( $cases as $index => $case ) {
        echo '| ', str_pad( $case_labels[ $index ], $pad, ' ', STR_PAD_LEFT );
        $result = Test::isSequential( $case[ 0 ] );
        echo $result ? ' | true ' : ' | false';
        if( $result == $case[ 1 ] ) {
            echo " | PASSED |\n";
        }
        else {
            echo " | FAILED |\n";
        }
    }
}

