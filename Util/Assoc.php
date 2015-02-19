<?php
/*****************************************************************************

Normalizes Critical Access to Associative Arrays
================================================

The PHP philosophy of forgiving unwise access to memory and extremely
enthusiastic automatic type conversion can cause a lot of problems when
presenting interfaces to untrusted input.  Modern PHP with
"production"-quality configurations is improving things.  However, the baggage
is still there, and I need clean, secure input.

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

require_once __DIR__ . '/Test.php';

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Implements a more formal way of checking and defaulting values stored in an
 * associative array.
 */
class Assoc implements
    \ArrayAccess,
    \Countable,
    \IteratorAggregate,
    \JsonSerializable,
    \Serializable {

    /*------------------------------------------------------------------------
    Public Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Protected Properties
    ------------------------------------------------------------------------*/

    protected $data;        //the internal data stored


    /*------------------------------------------------------------------------
    Private Properties
    ------------------------------------------------------------------------*/

    /*------------------------------------------------------------------------
    Public Methods
    ------------------------------------------------------------------------*/

    /**
     * Assoc object constructor.
     *
     * See the `init` parameter for typical initialization with a single
     * value.  Alternatively, the constructor accepts two other initialization
     * interfaces.
     *
     * Passing any number of arguments that are [ key, value ] pairs will be
     * used to initialize the new object.
     *
     * Passing an even-number of arguments where the even-numbered arguments
     * are all strings (used as keys) will be used as alternating keys and
     * values to initialize the new object.
     *
     * @param init Optional initial associative array initialization data.
     *             This can be any of the following types:
     *             - Associative array, copied as-is
     *             - Sequential array of [ key, value ] pairs
     *             - Assoc instance, data copied into new object
     *             - Iterable object, iteration output copied
     *             - Any other object, exposed properies copied
     *             This may also be one of the following (see above):
     *             - The first [ key, value ] pair of pair arguments
     *             - The first key in alternatiting key and value arguments
     * @throws     InvalidArgumentException if the initialization argument or
     *             arguments can not be used to initialize the object
     */
    public function __construct( $init = null ) {

        //initialize the internal data
        $this->data = [];

        //load data into the instance
        if( $init !== null ) {
            call_user_func_array( [ $this, 'update' ], func_get_args() );
        }
    }


    /**
     * Handles calls to undeclared methods.  The primary reason to do this is
     * to quickly add support for most "array_*" functions via method
     * invocation.
     *
     * @param name The name of the method to invoke
     * @param args The list of arguments passed to the method
     * @return     The return value of the method that was invoked
     * @throws     BadMethodCallException if the method is not supported
     */
    public function __call( $name, $args ) {

        //test for array_* methods
        if(
            ( substr( $name, 0, 6 ) == 'array_' )
            &&
            ( function_exists( $name ) == true )
        ) {

            //array_* function arguments
            array_unshift( $args, $this->data );

            //call the array function using our array data
            return call_user_func_array( $name, $args );
        }

        //method name not supported
        throw new BadMethodCallException(
            "Invalid method \"$name\" called for object."
        );
    }


    /**
     * Support property-style retrieval of values.  Why not?
     *
     * @param name The key into the associative array
     * @return     The value of the element in the array
     * @throws     OutOfBoundsException if the key does not exist
     */
    public function __get( $name ) {
        return $this->offsetGet( $name );
    }


    /**
     * Support property-style existence checking of values.
     *
     * @param name The key into the associative array
     * @return     True of the key exists
     */
    public function __isset( $name ) {
        return $this->offsetExists( $name );
    }


    /**
     * Support property-style alteration of values.
     *
     * @param name  The key into the associative array
     * @param value The new value to set in the array
     */
    public function __set( $name, $value ) {
        $this->offsetSet( $name, $value );
    }


    /**
     * Represents the object state as a string.
     *
     * @return A string representing the object
     */
    public function __toString() {

        //convert to string using JSON.
        return json_encode( $this->data );
    }


    /**
     * Support property-style removal of values.
     *
     * @param name The key into the associative array
     */
    public function __unset( $name ) {
        $this->offsetUnset( $name );
    }


    /**
     * Returns the number of elements in the array.  Supports the Countable
     * interface.
     *
     * @return The number of elements in the array
     */
    public function count() {
        return count( $this->data );
    }


    /**
     * Retrieves a value from the array, but adds the ability to supply a
     * default value if the key does not exist in the array.
     *
     * @param key     The key of the element from which to retrieve a value
     * @param default If the requested key does not exist, this value is
     *                returned (default: null)
     * @param empty   Also use default when empty (default: true)
     * @return        The value of the requested element, or the default value
     *                if it does not exist (or is empty)
     */
    public function get( $key, $default = null, $empty = true ) {
        if( $empty == true ) {
            if( $this->isEmpty( $key ) ) {
                return $default;
            }
            return $this->offsetGet( $key );
        }
        if( $this->offsetExists( $key ) ) {
            return $this->offsetGet( $key );
        }
        return $default;
    }


    /**
     * Returns an iterator for accessing the data in this object.
     *
     * @return An iterator for the associative array data
     */
    public function getIterator() {
        return new \ArrayIterator( $this->data );
    }


    /**
     * Tests if a key is present, and if it contains a non-empty value.  This
     * does NOT behave according to PHP's `empty()` function which allows for
     * several edge cases to be allowed as empty (e.g. a string containing
     * "0").
     *
     * @param key The key to check for emptiness
     * @return    If the key does not exist, or if it contains a value that
     *            should be considered empty, returns true.  Otherwise,
     *            returns false
     */
    public function isEmpty( $key ) {
        if( $this->offsetExists( $key ) == false ) {
            return true;
        }
        $value = $this->offsetGet( $key );
        return Test::isEmpty( $value );
    }


    /**
     * Provides support for JSON encoding of the object via the
     * JsonSerializable interface.
     *
     * @return The data that should be serialized by JSON encoding
     */
    public function jsonSerialize() {
        return $this->data;
    }


    /**
     * Allows loading data into the array from a JSON string.  This is not a
     * part of the JsonSerializable interface.
     *
     * @param json The JSON string to parse and load
     * @throws     RuntimeException if the JSON string can not be parsed
     */
    public function jsonUnserialize( $json ) {
        $data = json_decode( $json, true );
        $error = json_last_error();
        if( $error == JSON_ERROR_NONE ) {
            $this->load( $data );
        }
        else {
            throw new \RuntimeException(
                'Unable to load invalid JSON string.'
            );
        }
    }


    /**
     * Loads user data into the object after it has been constructed.  This
     * provides the same interface as the constructor, but any data that was
     * previously loaded or set may be overwritten (if the keys match).
     *
     * @param data The data to load (see `init` parameter for `__construct()`)
     * @throws     InvalidArgumentException if the initialization argument or
     *             arguments can not be used to initialize the object
     */
    public function update( $data ) {

        //native array given
        if( is_array( $data ) ) {

            //see if it's a numeric array
            if( Test::isSequential( $data ) ) {

                //merge data from key-value pairs
                $this->merge( array_column( $data, 1, 0 ) );
            }

            //associative array
            else {

                //merge as usual
                $this->merge( $data );
            }
        }

        //object given
        else if( is_object( $data ) ) {

            //when pulling data from Assoc objects, PHP will do the iteration
            //on accessible properties.  this won't work for us.
            if( $data instanceof Assoc ) {

                //merge from the source object's data property
                $this->merge( $data->data );
            }

            //see if the object can be iterated
            else if( $data instanceof Iterable ) {

                //merge as usual
                $this->merge( $data );
            }

            //not another Assoc object, or iterable
            else {

                //load exposed properties
                $this->merge( get_object_vars( $data ) );
            }
        }

        //finally, we can try to load from the argument list
        else {

            //list of arguments
            $args = func_get_args();

            //array of key-value pairs
            if( is_array( $args[ 0 ] ) ) {
                $this->merge( array_column( $args, 1, 0 ) );
            }

            //alternating array of keys and values
            else if( is_string( $args[ 0 ] ) ) {
                $num_args = count( $args );
                if( ( $num_args % 2 ) != 0 ) {
                    throw new \InvalidArgumentException(
                        'Invalid number of arguments for loading new array'
                        . ' data via paired arguments.'
                    );
                }
                $num_pairs = $num_args / 2;
                for( $i = 0; $i < $num_pairs; ++$i ) {
                    $this->data[ $args[ $i * 2 ] ] = $args[ ( $i * 2 ) + 1 ];
                }
            }

            //sorry, I've been too nice already
            else {
                throw new \InvalidArgumentException(
                    'Invalid argument(s) for loading new array data.'
                );
            }
        }
    }


    /**
     * Merges data from another Assoc object or an associative array.  This
     * method allows control over resolving merge conflicts via a callback
     * function.
     *
     * The callback function must accept three parameters.  The key of the
     * conflicting element, the target value (this instance) and the source
     * value (the incoming instance).  The function must return the value that
     * will be stored at the given key.
     *
     * @param incoming The incoming object or array from which to merge
     * @param resolver A callback function to resolve merge conflicts.  The
     *                 default resolver assumes the incoming values override
     *                 the current values.
     */
    public function merge( $incoming, $resolver = null ) {

        //check for default resolver
        if( $resolver === null ) {
            $resolver = function( $key, $mine, $theirs ) { return $theirs; };
        }

        //iterate through incoming data
        foreach( $incoming as $key => $value ) {

            //check for key conflict
            if( isset( $this->data[ $key ] ) ) {
                $this->data[ $key ] = $resolver(
                    $key, $this->data[ $key ], $incoming[ $key ]
                );
            }

            //key does not conflict
            else {
                $this->data[ $key ] = $incoming[ $key ];
            }
        }
    }


    /**
     * Tests if an element in the array exists.
     *
     * @param key The key to test
     * @return    True if the key exists in the array
     */
    public function offsetExists( $key ) {
        return isset( $this->data[ $key ] );
    }


    /**
     * Provides array subscript access to the elements in the array.
     *
     * @param key The key into the associative array
     * @return    The value at the requested key
     * @throws    OutOfBoundsException if the key is not valid
     */
    public function offsetGet( $key ) {
        if( isset( $this->data[ $key ] ) ) {
            return $this->data[ $key ];
        }
        throw new \OutOfBoundsException(
            "Unknown offset \"$key\" into array."
        );
    }


    /**
     * Sets values of elements in the array.
     *
     * @param key   The key of the element to set
     * @param value The value of the element
     */
    public function offsetSet( $key, $value ) {
        $this->data[ $key ] = $value;
    }


    /**
     * Deletes elements in the array.
     *
     * @param key The key of the element to delete
     * @throws    OutOfBoundsException if the key is not valid
     */
    public function offsetUnset( $key ) {
        if( isset( $this->data[ $key ] ) ) {
            unset( $this->data[ $key ] );
        }
        else {
            throw new \OutOfBoundsException(
                "Unknown offset \"$key\" into array."
            );
        }
    }


    /**
     * Serializes the array data.  Supports the Serializable interface.
     *
     * @return The serialized representation of the array data
     */
    public function serialize() {
        return serialize( $this->data );
    }


    /**
     * Unserializes the array data.  Supports the Serializable interface.
     * Note: This _destructively_ updates object state regardless of any data
     * that was previously stored in the array.
     *
     * @param data The serialized data to load into the object's state
     */
    public function unserialize( $data ) {
        $this->data = unserialize( $this->data );
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
 * PHP 5.5 provides this function for extracting columns from nested arrays.
 *
 * @param array      The array from which to extract columns
 * @param column_key The index into the nested arrays from which to gather
 *                   values for the output array.  If `null`, entire arrays
 *                   are used as their value.
 * @param index_key  Optionally specify an index into the nested arrays that
 *                   will be used as the keys to the values in the new array
 * @return           An array containing the values extracted from the nested
 *                   arrays
 */
if( function_exists( 'array_column' ) == false ) {
function array_column( array $array, $column_key, $index_key = null ) {
    if( $index_key !== null ) {
        return array_combine(
            array_column( $array, $index_key ),
            array_column( $array, $column_key )
        );
    }
    return array_map(
        function( $record ) {
            if( $column_key === null ) {
                return $record;
            }
            return $record[ $column_key ];
        },
        $array
    );
}
}


/*----------------------------------------------------------------------------
Execution
----------------------------------------------------------------------------*/

if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {

    header( 'Content-Type: text/plain; charset=utf-8' );

    //initializer array
    $init = [
        'a' => 1,
        'b' => 2,
        'c' => 0,
        'd' => '',
        'e' => '0',
        'f' => '0.0',
        'g' => false,
        'h' => null,
        'n' => [ 'na' => 10, 'nb' => 11, 'nc' => 12 ]
    ];

    //typical construction
    $assoc = new Assoc( $init );

    //automatic array methods
    echo "Automatic Array Methods\n";
    print_r( $assoc->array_keys() );
    print_r( $assoc->array_values() );
    echo "\n";

    //safe value retrieval interface
    $values = [];
    $values[] = $assoc->get( 'a' );
    $values[] = $assoc->get( 'b', 3 );
    $values[] = $assoc->get( 'z', 127 );
    echo "Safe Element Access\n";
    echo json_encode( $values ), "\n\n";
    if( $assoc->get( 'd', 'hello' ) != 'hello' ) {
        echo "## GET FAILED (EMPTY STRING CHECK) ##\n";
    }
    if( $assoc->get( 'e', 'hello' ) == 'hello' ) {
        echo "## GET FAILED (NON-EMPTY '0' CHECK) ##\n";
    }
    if( $assoc->get( 'f', 'hello' ) == 'hello' ) {
        echo "## GET FAILED (NON-EMPTY '0.0' CHECK) ##\n";
    }
    if( $assoc->get( 'g', 'hello', true ) != 'hello' ) {
        echo "## GET FAILED (EMPTY false CHECK) ##\n";
    }

    //proper error handling
    echo "Exception-based Error Handling\n";
    try {
        $unknown = $assoc[ 'z' ];
    }
    catch( \OutOfBoundsException $e ) {
        echo $e->getMessage(), "\n";
    }
    echo "\n";

    //property-style access
    echo "Property-style Access\n";
    echo $assoc->b, ' == ', $init[ 'b' ], "\n";
    $assoc->prop = 'hello';
    if( isset( $assoc->prop ) == false ) {
        echo "## FAILED TO SET NEW PROPERTY (PROPERTY) ##\n";
    }
    if( isset( $assoc[ 'prop' ] ) == false ) {
        echo "## FAILED TO SET NEW PROPERTY (ARRAY) ##\n";
    }
    unset( $assoc->prop );
    if( isset( $assoc[ 'prop' ] ) == true ) {
        echo "## FAILED TO UNSET PROPERTY ##\n";
    }
    echo "\n";

    echo "Merging\n";
    $merge_failed = false;

    //test basic merging
    $assoc->merge( [ 'b' => 9, 'c' => 10, 'm' => 11 ] );
    if( $assoc[ 'b' ] == $init[ 'b' ] ) {
        $merge_failed = true;
        echo "## MERGE FAILED (OVERWRITING) ##\n";
    }
    if( isset( $assoc[ 'm' ] ) == false ) {
        $merge_failed = true;
        echo "## MERGE FAILED (ADDING) ##\n";
    }

    //custom conflict resolution
    $merge = [ 'b' => 19, 'm' => 12 ];
    $assoc->merge(
        $merge,
        function( $key, $mine, $theirs ) { return $mine; }
    );
    if( $assoc[ 'b' ] == $merge[ 'b' ] ) {
        $merge_failed = true;
        echo "## MERGE FAILED (CUSTOM RESOLVER) ##\n";
    }

    if( $merge_failed == false ) {
        echo "All merge tests paseed.\n";
    }
    echo "\n";

}

