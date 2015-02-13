<?php
/*****************************************************************************

Extends Python-style Slicing in PHP for Arbitray Indexes
========================================================

This class adds the ability to handle negative start and stop indexes in
slices of known (bound) length.  Use the base `Slicer` class when the length
of the set is not known in advance.

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

require_once __DIR__ . '/Slicer.php';

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Provides additional slicing features for bound sets.
 *
 * Properties
 * ----------
 *
 * See the properties listed for the `Slicer` class for inherited properties.
 *
 * ### `int length`
 *
 * Gives the configured length of the sliced set.  If the length of the set is
 * unknown, this defaults to `null`.
 */
class BoundSlicer extends Slicer {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected $_get_start;  //overrides the reported start index
    protected $_get_stop;   //overrides the reported stop index
    protected $_length;     //the length of the bounded set


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Creates a slice object from a string representation of a slice.
     *
     * @param length The length of the bounded set that will be sliced.
     * @param string The slice as a string in the form of `start:stop:step`.
     *               The string may omit all or none of the values.
     * @return       A new Slicer instance with the given parameters
     */
    public static function createBoundSlicer( $length, $string ) {

        //get the slice values
        list( $start, $stop, $step ) = self::parseString( $string );

        //create the new slice object
        return new BoundSlicer( $length, $start, $stop, $step );
    }


    /**
     * Constructor.
     *
     * @param length Length of bounded set
     * @param start  The first reportable index in the slice (default: 0)
     * @param stop   The last reportable index in the slice (default: null/all)
     * @param step   The distance between each item in the slice (default: 1)
     */
    public function __construct(
        $length,
        $start = 0,
        $stop  = null,
        $step  = 1
    ) {

        //normalize the start and stop indexes
        $init_start = $start < 0 ? $length + $start : $start;
        $init_stop  = ( $stop !== null ) && ( $stop < 0 )
                    ? $length + $stop
                    : $stop;

        //initialize the parent's state
        parent::__construct( $init_start, $init_stop, $step );

        //set the reported start and stop indexes
        $this->_get_start = $start;
        $this->_get_stop  = $stop;

        //initialize the length
        $this->_length = $length;
    }


    /**
     * Allows read-only access to selected properties.
     *
     * @param name The name of the property to read
     * @return     The value of the property
     */
    public function __get( $name ) {
        if( ( $name == 'start' ) || ( $name == 'stop' ) ) {
            $property = '_get_' . $name;
            return $this->$property;
        }
        return parent::__get( $name );
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

/**
 * Tests the `BoundSlicer` slice object implementation.
 *
 */
function _test_bound_slicer() {

    //assume the tests will pass
    $result = true;

    //length of test range
    $length = 20;

    //slice test cases
    $cases = [

        //negative starts
        '-1:', '-1:1', '-1:1:1', '-10:', '-10:1',

        //negative stops
        '0:-5', '5:-5', '0:-5:2',

        //negative starts and stops
        '-1:-2', '-2:-1', '-10:-5', '-10:-2:3', '-18:-3:4',

        //negative steps (not supported)
        //'::-1', '::-2',
    ];

    //generate Python test code to determine expected results
    $lines = [ "r=range($length)" ];
    for( $i = 0; $i < count( $cases ); ++$i ) {
        $lines[] = "data.append(r[{$cases[$i]}])";
    }
    $expected = exec_python( $lines );

    //create a slice object for each test case, and compare to Python results
    for( $i = 0; $i < count( $cases ); ++$i ) {
        $next_failures = [];
        $slicer = BoundSlicer::createBoundSlicer( $length, $cases[ $i ] );
        $actual = [];
        for( $j = 0; $j < $length; ++$j ) {
            $next    = $slicer->getNext();
            $current = $slicer->current;
            if( $slicer->index() == true ) {
                $actual[] = $current;
                if( $current != $next ) {
                    $next_failures[] = "$current != $next";
                }
            }
            else if( $next !== false ) {
                if( $current == $next ) {
                    $next_failures[] = "$current == $next";
                }
            }
        }
        if( $expected[ $i ] != $actual ) {
            $result = false;
            echo 'Failed test case "' . $cases[ $i ] . "\"\n";
            echo " Parameters: {$slicer->start},",
                 " {$slicer->stop}, {$slicer->step}\n";
            echo ' E: [ ' . implode( ', ', $expected[ $i ] ) . " ]\n";
            echo ' A: [ ' . implode( ', ', $actual )   . " ]\n\n";
        }
        if( count( $next_failures ) != 0 ) {
            $result = false;
            echo 'Failed test case for "getNext()" method.', "\n  ";
            echo implode( "\n  ", $next_failures ), "\n";
        }
    }

    //return result of test
    return $result;
}


/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    require __DIR__ . '/exec_python.php';

    $result = _test_bound_slicer();
    if( $result == false ) {
        echo "Failed BoundSlicer test.";
        exit();
    }

    echo "All test cases passed.";

}

