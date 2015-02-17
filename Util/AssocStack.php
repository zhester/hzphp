<?php
/*****************************************************************************

Associative Array Object Stacking
=================================

One usage pattern for associative arrays is a simple information registry.
The registry pattern itself can be (and often is) completely implemented in a
single associative array.  A more powerful development of the registry pattern
uses multiple "stacked" associative arrays to provide a mechanism to override
or "mask" values by specifying new values in "higher" arrays in the stack.
The "lower" arrays still retain their identity, and their values (unlike the
typical OOP inheritence pattern).

ZIH - Extend: IterAssocStack
============================

- implement a key resolver that steps through each array in the stack
    - provide array_keys() and array_values() methods
    - enumerate()/items()
- implement iteration (Iterator or IteratorAggregate)
- implement Countable

ZIH - Extend: AssocRegistry
===========================

- implement a way to dereference from a selected level in the stack
- implement a way to determine what level in the stack held a key

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

require_once __DIR__ . '/Assoc.php';
require_once __DIR__ . '/NestedAssoc.php';

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/


/**
 * Implements a stack of Assoc objects.  Values in arrays added "later" in the
 * stack's lifetime will override values of the same key in arrays added
 * "earlier."
 */
class AssocStack implements
    \ArrayAccess,
    \JsonSerializable,
    \Serializable {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    //the stack of arrays and array-like objects
    protected $stack;


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Constructor.
     */
    public function __construct() {

        //initialize the stack of arrays
        $this->stack = [];

        //pull the constructor's arguments
        $args = func_get_args();
        $num_args = count( $args );

        //see if the user has passed an initial set of arrays
        if( $num_args > 0 ) {

            //iterate through the arguments
            foreach( $args as $array ) {

                //make sure the argument is something we can handle
                if(
                    ( $array instanceof ArrayAccess )
                    ||
                    ( is_array( $array ) )
                ) {

                    //add the array to the stack
                    $this->push( $array );
                }
            }
        }
    }


    /**
     * Represents the object state as a string.
     *
     * @return A string representing the object
     */
    public function __toString() {
        return json_encode( $this->jsonSerialize() );
    }


    /**
     * Provides support for JSON encoding of the object via the
     * JsonSerializable interface.
     *
     * @return The data that should be serialized by JSON encoding
     */
    public function jsonSerialize() {

        //create a temporary array-like object to assist with merging
        $temp = new NestedAssoc();

        //iterate through the stack from the bottom, up
        foreach( $this->stack as $array ) {

            //fully recursive merging, use incoming values over existing ones
            $temp->merge( $array, null, -1 );
        }

        //return the object that can be serialized by JSON
        return $temp;
    }


    /**
     * Checks for the existence of a key in the stack.
     *
     * @param key The key to test
     * @return    True of the key is present in the stack of arrays
     */
    public function offsetExists( $key ) {
        $num_arrays = count( $this->stack );
        for( $i = ( $num_arrays - 1 ); $i >= 0; --$i ) {
            if( isset( $this->stack[ $i ][ $key ] ) ) {
                return true;
            }
        }
        return false;
    }


    /**
     * Retrieves the top-most value for a key in the stack.
     *
     * @param key The key for which to retrieve
     * @return    The value of the element for the given key
     * @throws    OutOfBoundsException if the key does not exist in any array
     *            in the stack
     */
    public function offsetGet( $key ) {
        $num_arrays = count( $this->stack );
        for( $i = ( $num_arrays - 1 ); $i >= 0; --$i ) {
            if( isset( $this->stack[ $i ][ $key ] ) ) {
                return $this->stack[ $i ][ $key ];
            }
        }
        throw new \OutOfBoundsException(
            "Unable to find key \"$key\" in ($num_arrays) arrays."
        );
    }


    /**
     * Sets/updates a value in the top-most array in the stack.
     *
     * @param key   The key for the element to update
     * @param value The value to set in the element
     */
    public function offsetSet( $key, $value ) {
        $num_arrays = count( $this->stack );
        $this->stack[ ( $num_arrays - 1 ) ][ $key ] = $value;
    }


    /**
     * Removes ALL values in the array stack that match the given key.
     *
     * @param key The key of the elements to remove
     */
    public function offsetUnset( $key ) {
        $num_arrays = count( $this->stack );
        for( $i = ( $num_arrays - 1 ); $i >= 0; --$i ) {
            if( isset( $this->stack[ $i ][ $key ] ) ) {
                unset( $this->stack[ $i ][ $key ] );
            }
        }
    }


    /**
     * Adds an array to the array stack.
     *
     * @param array The array to add to the top of the stack
     */
    public function push( $array ) {
        $this->stack[] = $array;
    }


    /**
     * Serializes the state of all arrays in the stack.
     *
     * @return A string serializing the data in all arrays.
     */
    public function serialize() {
        return serialize( $this->stack );
    }


    /**
     * Unserializes the state of all arrays in the stack.
     *
     * @param state A string of serialized data in all arrays.
     */
    public function unserialize( $data ) {
        $this->stack = unserialize( $data );
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

    //list of associative arrays that will be progressively pushed onto stack
    $init = [
        [
            'a' => 1,
            'b' => 2,
            'c' => 3
        ],
        [
            'a' => 11,
            'd' => 12
        ],
        [
            'b' => 22
        ],
        new Assoc( [ 'b' => 32 ] ),
        new NestedAssoc( [ 'c' => 43 ] ),
        new NestedAssoc( [ 'n' => [ 'n0' => 0, 'n1' => 1 ] ] ),
        new NestedAssoc( [ 'n' => [ 'n1' => 11 ] ] )
    ];

    //generate test case data based on initilization
    $cases   = [];
    $allkeys = [];
    foreach( $init as $array ) {
        $case = [];
        $keys = [];
        foreach( $array as $k => $v ) {
            $case[] = [ $k, $v ];
            if( in_array( $k, $keys ) == false ) {
                $keys[] = $k;
            }
        }
        $cases[]   = $case;
        $allkeys[] = $keys;
    }

    //create the subject stack
    $stack = new AssocStack();

    $failed = false;

    echo "Testing overriding data with new arrays.\n";

    //progressively add new arrays to the stack, and test the updates
    foreach( $cases as $index => $case ) {

        //add the test array
        $stack->push( $init[ $index ] );

        //indicate the current state of the stack
        echo 'Pushing: ', json_encode( $init[ $index ] ), "\n";
        echo '  State: ', json_encode( $stack ), "\n";

        //check each key that should be in the stack at this point
        foreach( $allkeys[ $index ] as $key ) {
            if( isset( $stack[ $key ] ) == false ) {
                echo "  ## TEST FAILED ($index) MISSING $key ##\n";
                $failed = true;
            }
        }

        //check the values for the immediate test case
        foreach( $case as list( $k, $v ) ) {
            if( $stack[ $k ] != $v ) {
                echo "  ## TEST FAILED ($index) {$stack[$k]} != $v ##\n";
                $failed = true;
            }
        }
    }

    if( $failed == false ) {
        echo "All tests passed.\n";
    }

}

