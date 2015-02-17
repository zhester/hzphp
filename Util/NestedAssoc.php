<?php
/*****************************************************************************

Extends the Assoc Class to Support Additional Features for Nested Arrays
========================================================================

This object allows two additional forms of dereferencing array elements.
In addition to the normal string key indexes, users may also pass "paths"
into the array.  Key paths are given as either an array of keys that
successively descends into nested arrays, or as a key-path string where
successive keys are superated by forward slashes.

Normal shallow indexing:

    $value = $my_array[ 'my_key' ];

Normal nested indexing:

    $value = $my_array[ 'my_key' ][ 'my_nested_key' ];

Extended nested indexing:

    $value = $my_array[ [ 'my_key' ][ 'my_nested_key' ] ];

Path-style nested indexing:

    $value = $my_array[ 'my_key/my_nested_key' ];

As far as literal notation goes, neither of these are a huge savings.  The
real power comes from automated construction of paths into the array.
Furthermore, when using the recursive merging features, custom resolver
functions can receive these paths in a single argument, and do not have to
implement the looping to descend into the array.

*****************************************************************************/

/*----------------------------------------------------------------------------
Default Namespace
----------------------------------------------------------------------------*/

namespace hzphp\Util;

/*----------------------------------------------------------------------------
Dependencies
----------------------------------------------------------------------------*/

require_once __DIR__ . '/Assoc.php';

/*----------------------------------------------------------------------------
Classes
----------------------------------------------------------------------------*/

/**
 * Extends the Assoc class for nested arrays.
 */
class NestedAssoc extends Assoc {

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
     * Recursively merges data from another Assoc object or an associative
     * array.
     *
     * @param incoming The incoming object or array from which to merge
     * @param resolver A callback function to resolve merge conflicts.  The
     *                 default resolver assumes the incoming values override
     *                 the current values.
     * @param depth    Optionally, request recursive merging.  By default,
     *                 only shallow merging is performed (depth: 0).  To
     *                 recurse to all scalar values, use -1.  To recurse to
     *                 any given depth, pass that value.  When recursing, the
     *                 `key` parameter to the resolver becomes an array of
     *                 keys indicating the "path" into the array where the
     *                 first item is the key into the outermost array,
     *                 followed by the key of the first nested array, and so
     *                 on.
     */
    public function merge( $incoming, $resolver = null, $depth = 0 ) {

        //check for default resolver
        if( $resolver === null ) {
            $resolver = function( $key, $mine, $theirs ) { return $theirs; };
        }

        //see if we are currently recursing through all nested arrays
        if( $depth != 0 ) {

            //use the recursive merge method
            $this->submerge( $this->data, $incoming, $resolver, $depth );
        }

        //not currently recursing
        else {

            //perform shallow merge
            parent::merge( $incoming, $resolver );
        }
    }


    /**
     * Tests if an element in the array exists.
     *
     * @param key The key to test
     * @return    True if the key exists in the array
     */
    public function offsetExists( $key ) {
        if( is_array( $key ) ) {
            if( count( $key ) == 0 ) {
                return false;
            }
            $node = &$this->data;
            foreach( $key as $k ) {
                if( isset( $node[ $k ] ) == false ) {
                    return false;
                }
                $node = &$node[ $k ];
            }
            return true;
        }
        return parent::offsetExists( $key );
    }


    /**
     * Provides array subscript access to the elements in the array.
     *
     * @param key The key into the associative array
     * @return    The value at the requested key
     * @throws    OutOfBoundsException if the key is not valid
     */
    public function offsetGet( $key ) {
        if( is_array( $key ) ) {
            if( count( $key ) == 0 ) {
                throw new \OutOfBoundsException(
                    "Invalid offset (empty array) into array."
                );
            }
            $node = &$this->data;
            foreach( $key as $k ) {
                if( isset( $node[ $k ] ) == false ) {
                    $path = implode( '/', $key );
                    throw new \OutOfBoundsException(
                        "Unknown offset \"$path\" into array."
                    );
                }
                $node = &$node[ $k ];
            }
            return $node;
        }
        else if( is_string( $key ) && ( strpos( $key, '/' ) !== false ) ) {
            return $this->offsetGet( explode( '/', $key ) );
        }
        return parent::offsetGet( $key );
    }


    /**
     * Sets values of elements in the array.
     *
     * @param key   The key of the element to set
     * @param value The value of the element
     */
    public function offsetSet( $key, $value ) {
        if( is_array( $key ) ) {
            if( count( $key ) == 0 ) {
                throw new \OutOfBoundsException(
                    "Invalid offset (empty array) into array."
                );
            }
            $node = &$this->data;
            $last_key = array_pop( $key );
            foreach( $key as $k ) {
                if( isset( $node[ $k ] ) == false ) {
                    $node[ $k ] = [];
                }
                $node = &$node[ $k ];
            }
            $node[ $last_key ] = $value;
        }
        else if( is_string( $key ) && ( strpos( $key, '/' ) !== false ) ) {
            $this->offsetSet( explode( '/', $key ), $value );
        }
        else {
            parent::offsetSet( $key, $value );
        }
    }


    /**
     * Deletes elements in the array.
     *
     * @param key The key of the element to delete
     * @throws    OutOfBoundsException if the key is not valid
     */
    public function offsetUnset( $key ) {
        if( is_array( $key ) ) {
            if( count( $key ) == 0 ) {
                throw new \OutOfBoundsException(
                    "Invalid offset (empty array) into array."
                );
            }
            $node = &$this->data;
            $last_key = array_pop( $key );
            foreach( $key as $k ) {
                if( isset( $node[ $k ] ) == false ) {
                    $key[] = $last_key;
                    $path = implode( '/', $key );
                    throw new \OutOfBoundsException(
                        "Unknown offset \"$path\" into array."
                    );
                }
                $node = &$node[ $k ];
            }
            unset( $node[ $last_key ] );
        }
        else if( is_string( $key ) && ( strpos( $key, '/' ) !== false ) ) {
            $this->offsetUnset( explode( '/', $key ) );
        }
        else {
            parent::offsetUnset( $key );
        }
    }


    /*------------------------------------------------------------------------
    Protected Methods
    ------------------------------------------------------------------------*/

    /**
     * Recursively merges nested arrays into the target array.
     *
     * @param target   The target array to which we are merging
     * @param source   The source array from which we are merging
     * @param resolver The conflict resolution function
     * @param depth    The maximum depth of merging
     * @param path     Array of keys that can resolve the current element
     */
    protected function submerge(
        &$target,
        $source,
        $resolver,
        $depth,
        $path = null
    ) {

        //set up the nested key path
        $subpath = $path;
        $subpath_index = count( $subpath );
        $subpath[] = null;

        //iterate through the incoming data
        foreach( $source as $key => $value ) {

            //set the key at the end of the nested key path
            $subpath[ $subpath_index ] = $key;

            //check for scalar values, or if we are past the recursion limit
            if( is_scalar( $value ) || ( $depth == 0 ) ) {

                //check for merge conflict
                if( isset( $target[ $key ] ) ) {
                    $target[ $key ] = $resolver(
                        $subpath, $target[ $key ], $source[ $key ]
                    );
                }
                else {
                    $target[ $key ] = $source[ $key ];
                }
            }

            //this is a vector value, and we are within the recursion limit
            else {

                //merge vectors
                $this->submerge(
                    $target[ $key ],
                    $source[ $key ],
                    $resolver,
                    ( $depth - 1 ),
                    $subpath
                );
            }
        }
    }


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
    $assoc = new NestedAssoc( $init );

    //nested array accessors
    echo "Nested Array Access\n";
    echo $assoc[ [ 'n', 'nb' ] ], " == {$init['n']['nb']}\n";
    $assoc[ [ 'n', 'nc' ] ] = 112;
    echo $assoc[ [ 'n', 'nc' ] ], " != {$init['n']['nc']}\n";
    $assoc[ [ 'n', 'nd' ] ] = 42;
    unset( $assoc[ [ 'n', 'na' ] ] );
    if( isset( $assoc[ 'n' ][ 'na' ] ) ) {
        echo "## NESTED ACCESS FAILED (UNSET) ##\n";
    }
    echo "\n";

    //proper error handling
    echo "Exception-based Error Handling\n";
    try {
        $unknown = $assoc[ [ 'a', 'q' ] ];
    }
    catch( \OutOfBoundsException $e ) {
        echo $e->getMessage(), "\n";
    }
    echo "\n";

    //recursive merging
    $merge_failed = false;
    $merge = [ 'b' => 29, 'n' => [ 'nb' => 39 ] ];
    $assoc->merge( $merge, null, -1 );
    if( $assoc[ 'n' ][ 'nb' ] != $merge[ 'n' ][ 'nb' ] ) {
        $merge_failed = true;
        echo "## MERGE FAILED (RECURSION) ##\n";
    }
    if( $merge_failed == false ) {
        echo "All merge tests paseed.\n";
    }
    echo "\n";

}

