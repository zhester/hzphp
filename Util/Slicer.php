<?php
/*****************************************************************************

Implements Python-style Slicing in PHP for Arbitray Indexes
===========================================================

Python slice notation can be used to implement a consistent way of specifiying
subsets of results from various operations (array indexes, database result,
stream output, etc.).  This implements a way to add slice tracking to an
existing collection-oriented object.

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
 * Implements a helper class for tracking slices in a host object.
 *
 * The objective is to enable another object to give its users slice-style
 * access to subsets of list-oriented results.  The slicing can be done with
 * indefinite "streams" of result sets, or with deterministic result sets with
 * lengths known prior to slicing.
 *
 * Properties
 * ----------
 *
 * This class provides read-only access to the following properties.
 *
 * ### `int current`
 *
 * Gives the current state of the internal index.
 *
 * ### `int start`
 *
 * Gives the configured start index of the slice.
 *
 * ### `int step`
 *
 * Gives the configured step interval of the slice.
 *
 * ### `int stop`
 *
 * Gives the configured stop index of the slice.  If the remainder of the set
 * is needed, this is set to `null`.
 */
class Slicer {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected $_current;    //the current position in the slice
    protected $_start;      //start of slice
    protected $_step;       //distance between items in slice
    protected $_stop;       //end of slice


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Creates a slice object from a string representation of a slice.
     *
     * @param string The slice as a string in the form of `start:stop:step`.
     *               The string may omit all or none of the values.
     * @return       A new Slicer instance with the given parameters
     */
    public static function createSlicer( $string ) {

        //get the slice values
        list( $start, $stop, $step ) = self::parseString( $string );

        //create the new slice object
        return new Slicer( $start, $stop, $step );
    }


    /**
     * Constructs a range for a given slice specification.  This provides the
     * a way to generate ranges the same way Python does.  PHP's `range()`
     * handles edge cases slightly differently.
     *
     * @param start If `stop` is given, this is the first value in the range.
     *              If `stop` is omitted or `null`, this is the length of the
     *              range.
     * @param stop  One more than the last value of the range
     * @param step  The interval between items in the range (default: 1)
     * @return      An array representing the range
     */
    public static function makeRange( $start, $stop = null, $step = 1 ) {

        //step validity check
        if( $step == 0 ) {
            throw new \Exception( "Invalid step ($step) specified." );
        }

        //stop not specified (or left default)
        if( $stop === null ) {

            //use the start as the length of the range
            $stop  = $start;
            $start = 0;
        }

        //range list storage
        $range = [];

        //generate a decreasing range
        if( $step < 0 ) {
            for( $i = $start; $i > $stop; $i += $step ) {
                $range[] = $i;
            }
        }

        //generate an increasing range
        else {
            for( $i = $start; $i < $stop; $i += $step ) {
                $range[] = $i;
            }
        }

        //return the range
        return $range;
    }


    /**
     * Constructor.
     *
     * @param start The first reportable index in the slice (default: 0)
     * @param stop  The last reportable index in the slice (default: null/all)
     * @param step  The distance between each item in the slice (default: 1)
     */
    public function __construct(
        $start = 0,
        $stop  = null,
        $step  = 1
    ) {

        //validity check the step interval
        if( $step === 0 ) {
            throw new \Exception( 'Invalid step given.' );
        }
        else if( $step < 0 ) {
            throw new \Exception( 'Reverse stepping is not supported.' );
        }

        //nothing can be negative for an unbound slice
        if( ( $start < 0 ) || ( ( $step !== null ) && ( $step < 0 ) ) ) {
            throw new \Exception( 'Negative indexes are not allowed.' );
        }

        //initialize the start index
        $this->_start = $start === null ? 0 : $start;

        //initialize the stop index
        $this->_stop = $stop;

        //initialize the step interval
        $this->_step = $step === null ? 1 : $step;

        //initialize the internal index
        $this->_current = 0;
    }


    /**
     * Allows read-only access to selected properties.
     *
     * @param name The name of the property to read
     * @return     The value of the property
     */
    public function __get( $name ) {
        $properties = array_keys( get_object_vars( $this ) );
        $property   = '_' . $name;
        if( in_array( $property, $properties ) ) {
            return $this->$property;
        }
        return null;
    }


    /**
     * Represents the object state as a string.
     *
     * @return A string representing the object
     */
    public function __toString() {
        return "[{$this->_start}:{$this->_stop}:{$this->_step}";
    }


    /**
     * Retrieves the next valid slice index that can be reported.  If there
     * are no more valid slice indexes, this returns boolean false.
     *
     * @return Next valid slice index, or false if no more are allowed
     */
    public function getNext() {

        //if the slice is unbounded, or we are still before the stop
        if(
            ( $this->_stop === null )
            ||
            ( $this->_current < $this->_stop )
        ) {

            //see if we are before the start of the slice
            if( $this->_current < $this->_start ) {

                //report the starting index
                return $this->_start;
            }

            //determine the current step position and increment
            $step_position  = $this->_start - $this->_current;
            $step_increment = $step_position % $this->_step;

            //within bounds, report next valid index
            return $this->_current + $step_increment;
        }

        //the slice is a bounded slice, and we have exceeded it
        return false;
    }


    /**
     * Checks the current slice index to determine if it can be reported.
     *
     * @param increment Auto-increment internal slice index after checking the
     *                  current state.  Set to true to increment the internal
     *                  index.  Set to false to simply check the index state
     *                  without altering internal state.
     * @return          True if the current index is reportable, false if it
     *                  should not be reported
     */
    public function index( $increment = true ) {

        //assume the index can not be reported
        $reportable = false;

        //start index boundary
        if( $this->_current >= $this->_start ) {

            //stop index boundary
            if(
                ( $this->_stop === null )
                ||
                ( $this->_current < $this->_stop )
            ) {

                //determine the current step position
                $step_position = $this->_start - $this->_current;

                //test the step interval
                if( ( $step_position % $this->_step ) == 0 ) {

                    //this index is valid for the slice
                    $reportable = true;
                }
            }
        }

        //see if this call needs to update the internal index
        if( $increment !== false ) {
            $this->_current += 1;
        }

        //report whether or not the current index is reportable
        return $reportable;
    }


    /**
     * Resets the slice's internal state.
     *
     */
    public function reset() {
        $this->_current = 0;
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     * Parses a string representation of a slice, and provides the necessary
     * values used in creating slice objects.
     *
     * @param string The slice in its string form
     * @return       An array containing [ start, stop, step ]
     */
    protected static function parseString( $string ) {

        //default values for start, stop, and step
        $values = [ 0, null, 1 ];

        //make sure there is some slice specified
        if( $string != '' ) {

            //normal slice notation
            if( strpos( $string, ':' ) !== false ) {
                $parts = explode( ':', $string );
                for( $i = 0; $i < count( $parts ); ++$i ) {
                    if( strlen( $parts[ $i ] ) > 0 ) {
                        $values[ $i ] = intval( $parts[ $i ] );
                    }
                }
            }

            //single element
            else {
                $values[ 0 ] = intval( $string );
                $values[ 1 ] = $values[ 0 ] + 1;
            }
        }

        //return the slice values
        return $values;
    }


    /*------------------------------------------------------------------------
    Private Methods
    ------------------------------------------------------------------------*/

}


/*----------------------------------------------------------------------------
Functions
----------------------------------------------------------------------------*/

/**
 * Tests the `Slicer::makeRange()` static method.
 *
 */
function _test_range() {

    //assume the tests will pass
    $result = true;

    //length of test ranges
    $length = 10;

    //test cases for `range()` parameters
    $cases = [
        [  0, null, 1 ], [  1, null, 1 ], [  2, null,  1 ],
        [  0, 0,    1 ], [  0,  1,   1 ], [  1,  1,    1 ],
        [  2, 1,   -1 ], [  0, 10,   2 ], [  0, 10,   -1 ],
        [ 10, 1,   -2 ], [ -1,  1,   1 ], [ -2,  2,    1 ],
        [ -2, 2,    2 ],
    ];

    //generate Python code lines to get expected ranges
    $lines = [];
    for( $i = 0; $i < count( $cases ); ++$i ) {
        $case = $cases[ $i ];
        $args = $case[ 1 ] === null
                ? strval( $case[ 0 ] )
                : implode( ',', $case );
        $lines[] = "data.append(range($args))";
    }
    $expected = exec_python( $lines );

    //execute the `Slicer::makeRange()` method for actual results
    for( $i = 0; $i < count( $cases ); ++$i ) {
        $case = $cases[ $i ];
        $actual = Slicer::makeRange( $case[ 0 ], $case[ 1 ], $case[ 2 ] );
        if( $expected[ $i ] != $actual ) {
            $result = false;
            echo "Failed range test case ( ",
                    implode( ', ', $case ) . " )\n";
            echo " E: [ " . implode( ', ', $expected[ $i ] ) . " ]\n";
            echo " A: [ " . implode( ', ', $actual )         . " ]\n\n";
        }
    }

    //return testing result
    return $result;
}


/**
 * Tests the `Slicer` slice object implementation.
 *
 */
function _test_slicer() {

    //assume the tests will pass
    $result = true;

    //length of test range
    $length = 20;

    //slice test cases
    $cases = [

        //boundaries and edge cases
        '::', '0:', '0::', '0:1', '0:0:', '0::1', '::10', '::19', '::20',

        //normal ranges
        '1:', '1::', '2::', '10::', '10:0:', '10:2:', '10:18:3', '2:17:4',
        '::2', '::3', '::4',
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
        $slicer = Slicer::createSlicer( $cases[ $i ] );
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

    $result = _test_range();
    if( $result == false ) {
        echo "Failed makeRange test.";
        exit();
    }

    $result = _test_slicer();
    if( $result == false ) {
        echo "Failed Slicer test.";
        exit();
    }

    echo "All test cases passed.";

}

